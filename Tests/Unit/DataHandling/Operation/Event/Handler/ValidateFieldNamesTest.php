<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use PHPUnit\Framework\Attributes\Test;
use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\ValidateFieldNames;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationSetupEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\ConflictException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\UpdateRecordOperation;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ValidateFieldNamesTest extends UnitTestCase
{
    #[Test]
    public function willExitEarlyOnDeleteOperation(): void
    {
        $mockOperation = $this->createMock(DeleteRecordOperation::class);

        $mockOperation
            ->expects(self::never())
            ->method('getDataForDataHandler');

        $event = new RecordOperationSetupEvent($mockOperation);

        (new ValidateFieldNames())($event);
    }

    #[Test]
    public function willThrowExceptionIfFieldDoesNotExistInTca(): void
    {
        $GLOBALS['TCA']['tablename']['columns'] = [
            'definedField1' => [],
            'definedField2' => [],
        ];

        foreach ([UpdateRecordOperation::class, CreateRecordOperation::class] as $operationClass) {
            $mockOperation = $this->createMock($operationClass);

            $mockOperation
                ->expects(self::once())
                ->method('getTable')
                ->willReturn('tablename');

            $mockOperation
                ->expects(self::once())
                ->method('getDataForDataHandler')
                ->willReturn([
                    'definedField1' => 'definedField1Value',
                    'definedField2' => 'definedField2Value',
                    'undefinedField1' => 'undefinedField1Value',
                    'undefinedField2' => 'undefinedField2Value',
                ]);

            $event = new RecordOperationSetupEvent($mockOperation);

            self::expectException(ConflictException::class);
            self::expectExceptionCode(1634119601036);

            (new ValidateFieldNames())($event);
        }
    }

    #[Test]
    public function willNotThrowExceptionIfAllFieldsExistInTca(): void
    {
        $GLOBALS['TCA']['tablename']['columns'] = [
            'definedField1' => [],
            'definedField2' => [],
        ];

        foreach ([UpdateRecordOperation::class, CreateRecordOperation::class] as $operationClass) {
            $mockOperation = $this->createMock($operationClass);

            $mockOperation
                ->expects(self::once())
                ->method('getTable')
                ->willReturn('tablename');

            $mockOperation
                ->expects(self::once())
                ->method('getDataForDataHandler')
                ->willReturn([
                    'definedField1' => 'definedField1Value',
                    'definedField2' => 'definedField2Value',
                ]);

            $event = new RecordOperationSetupEvent($mockOperation);

            (new ValidateFieldNames())($event);
        }
    }
}
