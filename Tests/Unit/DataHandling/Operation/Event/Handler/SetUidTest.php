<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\DataHandler;
use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\SetUid;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationInvocationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\UpdateRecordOperation;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SetUidTest extends UnitTestCase
{
    #[Test]
    public function setsUidOnCreateOperationIfNotAlreadySet(): void
    {
        $mockDataHandler = $this->createMock(DataHandler::class);

        $mockDataHandler->substNEWwithIDs = [
            StringUtility::getUniqueId() => 123,
            StringUtility::getUniqueId() => 321,
        ];

        $mockOperation = $this->createMock(CreateRecordOperation::class);

        $mockOperation
            ->expects(self::once())
            ->method('isSuccessful')
            ->willReturn(true);

        $mockOperation
            ->expects(self::once())
            ->method('getUid')
            ->willReturn(0);

        $mockOperation
            ->expects(self::once())
            ->method('getDataHandler')
            ->willReturn($mockDataHandler);

        $mockOperation
            ->expects(self::once())
            ->method('setUid')
            ->with(123);

        $event = new RecordOperationInvocationEvent($mockOperation);

        (new SetUid())($event);
    }

    #[Test]
    public function doesNotSetUidIfOperationUnsuccessful(): void
    {
        $mockOperation = $this->createMock(CreateRecordOperation::class);

        $mockOperation
            ->expects(self::once())
            ->method('isSuccessful')
            ->willReturn(false);

        $mockOperation
            ->method('getUid')
            ->willReturn(0);

        $mockOperation
            ->expects(self::never())
            ->method('getDataHandler');

        $mockOperation
            ->expects(self::never())
            ->method('setUid');

        $event = new RecordOperationInvocationEvent($mockOperation);

        (new SetUid())($event);
    }

    #[Test]
    public function doesNotSetUidIfUidIsSet(): void
    {
        $mockOperation = $this->createMock(CreateRecordOperation::class);

        $mockOperation
            ->expects(self::once())
            ->method('isSuccessful')
            ->willReturn(true);

        $mockOperation
            ->expects(self::once())
            ->method('getUid')
            ->willReturn(1);

        $mockOperation
            ->expects(self::never())
            ->method('getDataHandler');

        $mockOperation
            ->expects(self::never())
            ->method('setUid');

        $event = new RecordOperationInvocationEvent($mockOperation);

        (new SetUid())($event);
    }

    #[Test]
    public function doesNotSetUidIfOperationIsNotCreate(): void
    {
        foreach ([UpdateRecordOperation::class, DeleteRecordOperation::class] as $operationClass) {
            $mockOperation = $this->createMock($operationClass);

            $mockOperation
                ->method('isSuccessful')
                ->willReturn(true);

            $mockOperation
                ->method('getUid')
                ->willReturn(0);

            $mockOperation
                ->expects(self::never())
                ->method('getDataHandler');

            $mockOperation
                ->expects(self::never())
                ->method('setUid');

            $event = new RecordOperationInvocationEvent($mockOperation);

            (new SetUid())($event);
        }
    }
}
