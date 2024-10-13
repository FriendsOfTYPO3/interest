<?php

declare(strict_types=1);

namespace Pixelant\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use Pixelant\Interest\DataHandling\Operation\CreateRecordOperation;
use Pixelant\Interest\DataHandling\Operation\DeleteRecordOperation;
use Pixelant\Interest\DataHandling\Operation\Event\Handler\ApplyFieldDataTransformations;
use Pixelant\Interest\DataHandling\Operation\Event\RecordOperationSetupEvent;
use Pixelant\Interest\DataHandling\Operation\UpdateRecordOperation;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ApplyFieldDataTransformationsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function returnsEarlyIfDeleteRecordOperation(): void
    {
        $mockOperation = $this->createMock(DeleteRecordOperation::class);

        $mockOperation
            ->expects(self::never())
            ->method('getSettings');

        $event = new RecordOperationSetupEvent($mockOperation);

        (new ApplyFieldDataTransformations())($event);
    }

    /**
     * @test
     */
    public function callsStdWrapWithCorrectArguments(): void
    {
        foreach ([CreateRecordOperation::class, UpdateRecordOperation::class] as $operationClass) {
            $mockOperation = $this->createMock($operationClass);

            $field1 = ['randomData' => StringUtility::getUniqueId()];

            $field2 = ['alsoRandomData' => StringUtility::getUniqueId()];

            $settingsArray = [
                'transformations.' => [
                    'tablename' => [],
                    'tablename.' => [
                        'field1.' => $field1,
                        'field2.' => $field2,
                    ],
                ],
            ];

            $dataArray = [
                'field1' => StringUtility::getUniqueId(),
                'field2' => StringUtility::getUniqueId(),
            ];

            $mockOperation
                ->expects(self::once())
                ->method('getSettings')
                ->willReturn($settingsArray);

            $mockOperation
                ->method('getTable')
                ->willReturn('tablename');

            $mockContentObjectRenderer = $this->createMock(ContentObjectRenderer::class);

            $invocationCount = self::exactly(2);

            $mockContentObjectRenderer
                ->expects($invocationCount)
                ->method('stdWrap')
                ->willReturnCallback(function ($parameters) use ($invocationCount, $dataArray) {
                    $this->assertSame($dataArray[$invocationCount->numberOfInvocations() - 1], $parameters);

                    return $invocationCount->numberOfInvocations();
                });

            $invocationCount = self::exactly(2);

            $mockOperation
                ->expects($invocationCount)
                ->method('setDataFieldForDataHandler')
                ->willReturnCallback(function ($parameters) use ($invocationCount) {
                    match ($invocationCount->numberOfInvocations()) {
                        1 => $this->assertSame(['field1', 'field1return'], $parameters),
                        2 => $this->assertSame(['field2', 'field2return'], $parameters),
                        default => $this->fail(),
                    };

                    return $invocationCount->numberOfInvocations();
                });

            $mockOperation
                ->method('getContentObjectRenderer')
                ->willReturn($mockContentObjectRenderer);

            $mockOperation
                ->method('getDataForDataHandler')
                ->willReturn($dataArray);

            $event = new RecordOperationSetupEvent($mockOperation);

            (new ApplyFieldDataTransformations())($event);
        }
    }
}
