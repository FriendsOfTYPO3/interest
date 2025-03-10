<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\DataHandler;
use FriendsOfTYPO3\Interest\DataHandling\Operation\AbstractRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\Message\RelationFieldValueMessage;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\RegisterValuesOfRelationFields;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationInvocationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\UpdateRecordOperation;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RegisterValuesOfRelationFieldsTest extends UnitTestCase
{
    #[Test]
    public function returnEarlyWhenNotUpdateOperation(): void
    {
        foreach ([DeleteRecordOperation::class, CreateRecordOperation::class] as $operationClass) {
            $mockOperation = $this->createMock($operationClass);

            $mockOperation
                ->expects(self::never())
                ->method('getDataHandler');

            $event = new RecordOperationInvocationEvent($mockOperation);

            (new RegisterValuesOfRelationFields())($event);
        }
    }

    #[Test]
    public function attemptToProcessDatamapWhenUpdateOperation(): void
    {
        $tableName = 'tablename';

        $mockDataHandler = $this->createMock(DataHandler::class);

        $mockDataHandler->datamap = [$tableName => []];

        $mockOperation = $this->createMock(UpdateRecordOperation::class);

        $mockOperation
            ->method('getTable')
            ->willReturn($tableName);

        $mockOperation
            ->expects(self::once())
            ->method('getDataHandler')
            ->willReturn($mockDataHandler);

        $event = new RecordOperationInvocationEvent($mockOperation);

        (new RegisterValuesOfRelationFields())($event);
    }

    #[Test]
    public function correctlyDispatchRelationFieldValueMessage(): void
    {
        $tableName = 'tablename';

        $mockDataHandler = $this->createMock(DataHandler::class);

        $mockDataHandler->datamap = [
            $tableName => [
                123 => [
                    'fieldWithForeignField' => 'fieldWithForeignFieldValue',
                    'fieldWithoutForeignField' => 'fieldWithoutForeignFieldValue',
                ],
            ],
        ];

        $mockOperation = $this->createMock(UpdateRecordOperation::class);

        $mockOperation
            ->method('getTable')
            ->willReturn($tableName);

        $mockOperation
            ->expects(self::once())
            ->method('getDataHandler')
            ->willReturn($mockDataHandler);

        $mockOperation
            ->expects(self::once())
            ->method('dispatchMessage')
            ->with(self::callback(function (RelationFieldValueMessage $message) use ($tableName) {
                self::assertEquals($tableName, $message->getTable());
                self::assertEquals('fieldWithForeignField', $message->getField());
                self::assertEquals(123, $message->getId());
                self::assertEquals('fieldWithForeignFieldValue', $message->getValue());

                return true;
            }));

        $partialMockEventHandler = $this->createPartialMock(
            RegisterValuesOfRelationFields::class,
            ['getTcaFieldConfigurationAndRespectColumnOverrides']
        );

        $partialMockEventHandler
            ->expects(self::exactly(2))
            ->method('getTcaFieldConfigurationAndRespectColumnOverrides')
            ->willReturnCallback(
                function (AbstractRecordOperation $recordOperation, string $field): array {
                    switch ($field) {
                        case 'fieldWithForeignField':
                            return ['foreign_field' => 'foreignFieldName'];
                        case 'fieldWithoutForeignField':
                            return [];
                        default:
                            throw new \UnexpectedValueException('Unexpected field name in test: ' . $field);
                    }
                }
            );

        $event = new RecordOperationInvocationEvent($mockOperation);

        $partialMockEventHandler($event);
    }
}
