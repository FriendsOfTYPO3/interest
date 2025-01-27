<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\SanitizeFieldValues;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationSetupEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\InvalidArgumentException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\UpdateRecordOperation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SanitizeFieldValuesTest extends UnitTestCase
{
    #[Test]
    public function returnEarlyIfDeleteOperation(): void
    {
        $mockOperation = $this->createMock(DeleteRecordOperation::class);

        $mockOperation
            ->expects(self::never())
            ->method('getDataForDataHandler');

        $event = new RecordOperationSetupEvent($mockOperation);

        (new SanitizeFieldValues())($event);
    }

    #[Test]
    public function csvRelationalFieldsAreExploded(): void
    {
        $dataForDataHandler = [
            'arrayRelationField' => ['relation1RemoteId', 'relation2RemoteId', 'relation3RemoteId'],
            'csvRelationField' => 'relation4RemoteId,relation5RemoteId,relation6RemoteId',
        ];

        foreach ([CreateRecordOperation::class, UpdateRecordOperation::class] as $operationClass) {
            $mockOperation = $this->createMock($operationClass);

            $mockOperation
                ->expects(self::once())
                ->method('getDataForDataHandler')
                ->willReturn($dataForDataHandler);

            $mockOperation
                ->expects(self::once())
                ->method('setDataFieldForDataHandler')
                ->with('csvRelationField', ['relation4RemoteId', 'relation5RemoteId', 'relation6RemoteId']);

            $partialMockEventHandler = $this->createPartialMock(
                SanitizeFieldValues::class,
                ['isRelationalField']
            );

            $partialMockEventHandler
                ->method('isRelationalField')
                ->willReturn(true);

            $event = new RecordOperationSetupEvent($mockOperation);

            $partialMockEventHandler($event);
        }
    }

    #[Test]
    public function floatIntegerAndStringAreNotModified(): void
    {
        $dataForDataHandler = [
            'floatField' => 1.234,
            'integerField' => 56,
            'stringField' => 'aStringValue',
        ];

        foreach ([CreateRecordOperation::class, UpdateRecordOperation::class] as $operationClass) {
            $mockOperation = $this->createMock($operationClass);

            $mockOperation
                ->expects(self::once())
                ->method('getDataForDataHandler')
                ->willReturn($dataForDataHandler);

            $mockOperation
                ->expects(self::never())
                ->method('setDataFieldForDataHandler');

            $partialMockEventHandler = $this->createPartialMock(
                SanitizeFieldValues::class,
                ['isRelationalField']
            );

            $partialMockEventHandler
                ->method('isRelationalField')
                ->willReturn(false);

            $event = new RecordOperationSetupEvent($mockOperation);

            $partialMockEventHandler($event);
        }
    }

    #[Test]
    #[DataProvider('unsupportedValueTypeDataProvider')]
    public function unsupportedValueTypeThrowsException(array $dataForDataHandler): void
    {
        foreach ([CreateRecordOperation::class, UpdateRecordOperation::class] as $operationClass) {
            $mockOperation = $this->createMock($operationClass);

            $mockOperation
                ->expects(self::once())
                ->method('getDataForDataHandler')
                ->willReturn($dataForDataHandler);

            $mockOperation
                ->expects(self::never())
                ->method('setDataFieldForDataHandler');

            $partialMockEventHandler = $this->createPartialMock(
                SanitizeFieldValues::class,
                ['isRelationalField']
            );

            $partialMockEventHandler
                ->method('isRelationalField')
                ->willReturn(false);

            $event = new RecordOperationSetupEvent($mockOperation);

            self::expectException(InvalidArgumentException::class);

            $partialMockEventHandler($event);
        }
    }

    public static function unsupportedValueTypeDataProvider(): array
    {
        return [
            [['objectField' => new \stdClass()]],
            [['resourceField' => fopen('php://memory', 'r')]],
            [['nullField' => null]],
        ];
    }
}
