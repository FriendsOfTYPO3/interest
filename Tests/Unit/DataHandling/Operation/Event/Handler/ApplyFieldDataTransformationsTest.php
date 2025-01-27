<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\ApplyFieldDataTransformations;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationSetupEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\UpdateRecordOperation;
use PHPUnit\Framework\Attributes\Test;
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
                ->willReturnCallback(function ($parameter) use ($invocationCount, $dataArray) {
                    self::assertEquals(
                        $dataArray[array_keys($dataArray)[$invocationCount->numberOfInvocations() - 1]],
                        $parameter
                    );

                    return match ($invocationCount->numberOfInvocations()) {
                        1 => 'field1return',
                        2 => 'field2return',
                        default => self::fail('Number of invocations does not match number of fields'),
                    };
                });

            $invocationCount = self::exactly(2);

            $mockOperation
                ->expects($invocationCount)
                ->method('setDataFieldForDataHandler')
                ->willReturnCallback(function ($parameter1, $parameter2) use ($invocationCount) {
                    switch ($invocationCount->numberOfInvocations()) {
                        case 1:
                            self::assertEquals('field1', $parameter1);
                            self::assertEquals('field1return', $parameter2);
                            return;
                        case 2:
                            self::assertEquals('field2', $parameter1);
                            self::assertEquals('field2return', $parameter2);
                            return;
                    }

                    self::fail('Number of invocations does not match number of fields');
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
