<?php

declare(strict_types=1);

namespace Pixelant\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use PHPUnit\Framework\Attributes\Test;
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
    #[Test]
    public function returnsEarlyIfDeleteRecordOperation(): void
    {
        $mockOperation = $this->createMock(DeleteRecordOperation::class);

        $mockOperation
            ->expects(self::never())
            ->method('getSettings');

        $event = new RecordOperationSetupEvent($mockOperation);

        (new ApplyFieldDataTransformations())($event);
    }

    #[Test]
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
                    self::assertSame(
                        $dataArray[array_keys($dataArray)[$invocationCount->numberOfInvocations() - 1]],
                        $parameters
                    );

                    return $invocationCount->numberOfInvocations();
                });

            $invocationCount = self::exactly(2);

            $mockOperation
                ->expects($invocationCount)
                ->method('setDataFieldForDataHandler')
                ->willReturnCallback(function ($parameters) use ($invocationCount) {
                    switch ($invocationCount->numberOfInvocations()) {
                        case 1:
                            self::assertSame('field1', $parameters);
                            return 'field1return';
                        case 2:
                            self::assertSame('field2', $parameters);
                            return 'field2return';
                    }

                    self::fail();
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
