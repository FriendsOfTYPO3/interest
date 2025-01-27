<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\RemoveFieldsWithNullValue;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationSetupEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\UpdateRecordOperation;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RemoveFieldsWithNullValueTest extends UnitTestCase
{
    #[Test]
    public function returnEarlyIfDeleteOperation(): void
    {
        $mockOperation = $this->createMock(DeleteRecordOperation::class);

        $mockOperation
            ->expects(self::never())
            ->method('getDataForDataHandler');

        $event = new RecordOperationSetupEvent($mockOperation);

        (new RemoveFieldsWithNullValue())($event);
    }

    #[Test]
    public function correctlyRemovesEmptyValuesFromRelationArrays(): void
    {
        $dataForDataHandler = [
            'nonRelationField' => 'nonRelationFieldValue',
            'emptyRelationField' => [],
            'relationFieldWithNoEmptyValues' => ['remoteId1', 'remoteId2', 'remoteId3'],
            'relationFieldWithSomeNullValues' => ['remoteId4', null, null, 'remoteId5', 'remoteId6'],
            'relationFieldWithOnlyNullValues' => [null, null, null],
            'nullValueField1' => null,
            'nullValueField2' => null,
        ];

        foreach ([CreateRecordOperation::class, UpdateRecordOperation::class] as $operationClass) {
            $mockOperation = $this->createMock($operationClass);

            $mockOperation
                ->expects(self::once())
                ->method('getDataForDataHandler')
                ->willReturn($dataForDataHandler);

            $invocationCount = self::exactly(2);

            $mockOperation
                ->expects($invocationCount)
                ->method('unsetDataField')
                ->willReturnCallback(function ($parameter) use ($invocationCount) {
                    match ($invocationCount->numberOfInvocations()) {
                        1 => self::assertEquals('nullValueField1', $parameter),
                        2 => self::assertEquals('nullValueField2', $parameter),
                        default => self::fail(),
                    };

                    return $invocationCount->numberOfInvocations();
                });

            $event = new RecordOperationSetupEvent($mockOperation);

            (new RemoveFieldsWithNullValue())($event);
        }
    }
}
