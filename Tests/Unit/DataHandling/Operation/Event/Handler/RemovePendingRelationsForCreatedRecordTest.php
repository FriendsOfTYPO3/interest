<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\RemovePendingRelationsForCreatedRecord;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationInvocationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\UpdateRecordOperation;
use FriendsOfTYPO3\Interest\Domain\Repository\PendingRelationsRepository;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RemovePendingRelationsForCreatedRecordTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function doesNotProceedIfOperationWasUnsuccessful(): void
    {
        $mockRepository = $this->createMock(PendingRelationsRepository::class);

        $mockRepository
            ->expects(self::never())
            ->method('removeRemote');

        GeneralUtility::setSingletonInstance(PendingRelationsRepository::class, $mockRepository);

        $mockOperation = $this->createMock(CreateRecordOperation::class);

        $mockOperation
            ->expects(self::once())
            ->method('isSuccessful')
            ->willReturn(false);

        $event = new RecordOperationInvocationEvent($mockOperation);

        (new RemovePendingRelationsForCreatedRecord())($event);
    }

    #[Test]
    public function doesNotProceedWhenUpdateOrDeleteOperation(): void
    {
        $mockRepository = $this->createMock(PendingRelationsRepository::class);

        $mockRepository
            ->expects(self::never())
            ->method('removeRemote');

        GeneralUtility::setSingletonInstance(PendingRelationsRepository::class, $mockRepository);

        foreach ([UpdateRecordOperation::class, DeleteRecordOperation::class] as $operationClass) {
            $mockOperation = $this->createMock($operationClass);

            $mockOperation
                ->expects(self::once())
                ->method('isSuccessful')
                ->willReturn(true);

            $event = new RecordOperationInvocationEvent($mockOperation);

            (new RemovePendingRelationsForCreatedRecord())($event);
        }
    }

    #[Test]
    public function removeRemoteIsCalledWithRemoteId(): void
    {
        $mockRepository = $this->createMock(PendingRelationsRepository::class);

        $mockRepository
            ->expects(self::exactly(1))
            ->method('removeRemote')
            ->with('remoteId');

        GeneralUtility::setSingletonInstance(PendingRelationsRepository::class, $mockRepository);

        $mockOperation = $this->createMock(CreateRecordOperation::class);

        $mockOperation
            ->expects(self::once())
            ->method('isSuccessful')
            ->willReturn(true);

        $mockOperation
            ->expects(self::once())
            ->method('getRemoteId')
            ->willReturn('remoteId');

        $event = new RecordOperationInvocationEvent($mockOperation);

        (new RemovePendingRelationsForCreatedRecord())($event);
    }
}
