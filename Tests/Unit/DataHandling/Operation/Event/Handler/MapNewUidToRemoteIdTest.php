<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use PHPUnit\Framework\Attributes\Test;
use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\MapNewUidToRemoteId;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationInvocationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\UpdateRecordOperation;
use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordInstanceIdentifier;
use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordRepresentation;
use FriendsOfTYPO3\Interest\Domain\Repository\RemoteIdMappingRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class MapNewUidToRemoteIdTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function returnEarlyIfDeleteOperation(): void
    {
        $mockOperation = $this->createMock(DeleteRecordOperation::class);

        $mockOperation
            ->expects(self::never())
            ->method('isSuccessful');

        $event = new RecordOperationInvocationEvent($mockOperation);

        (new MapNewUidToRemoteId())($event);
    }

    #[Test]
    public function returnEarlyIfUnsuccessfulOperation(): void
    {
        $mappingRepository = $this->createMock(RemoteIdMappingRepository::class);

        $mappingRepository
            ->expects(self::never())
            ->method(self::anything());

        GeneralUtility::setSingletonInstance(RemoteIdMappingRepository::class, $mappingRepository);

        foreach ([CreateRecordOperation::class, UpdateRecordOperation::class] as $operationClass) {
            $mockOperation = $this->createMock($operationClass);

            $mockOperation
                ->expects(self::once())
                ->method('isSuccessful')
                ->willReturn(false);

            $event = new RecordOperationInvocationEvent($mockOperation);

            (new MapNewUidToRemoteId())($event);
        }
    }

    #[Test]
    public function setsUidOnCreateOperation(): void
    {
        $remoteId = 'theRemoteId';

        $mockInstanceIdentifier = $this->createMock(RecordInstanceIdentifier::class);

        $mockInstanceIdentifier
            ->method('getRemoteIdWithAspects')
            ->willReturn($remoteId);

        $mockRecordRepresentation = $this->createMock(RecordRepresentation::class);

        $mockRecordRepresentation
            ->method('getRecordInstanceIdentifier')
            ->willReturn($mockInstanceIdentifier);

        $mockOperation = $this->createMock(CreateRecordOperation::class);

        $mockOperation
            ->expects(self::once())
            ->method('isSuccessful')
            ->willReturn(true);

        $mockOperation
            ->method('getRemoteId')
            ->willReturn($remoteId);

        $mockOperation
            ->method('getUid')
            ->willReturn(1234);

        $mockOperation
            ->method('getTable')
            ->willReturn('tablename');

        $mockOperation
            ->expects(self::once())
            ->method('setUid')
            ->with(1234);

        $mockOperation
            ->method('getRecordRepresentation')
            ->willReturn($mockRecordRepresentation);

        $mappingRepository = $this->createMock(RemoteIdMappingRepository::class);

        $mappingRepository
            ->expects(self::once())
            ->method('exists')
            ->with('theRemoteId')
            ->willReturn(false);

        $mappingRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                'theRemoteId',
                'tablename',
                1234,
                $mockOperation
            );

        $mappingRepository
            ->expects(self::once())
            ->method('get')
            ->with('theRemoteId')
            ->willReturn(1234);

        GeneralUtility::setSingletonInstance(RemoteIdMappingRepository::class, $mappingRepository);

        $event = new RecordOperationInvocationEvent($mockOperation);

        (new MapNewUidToRemoteId())($event);
    }

    #[Test]
    public function executesMappingRepositoryUpdateOnUpdateOperation(): void
    {
        $mockOperation = $this->createMock(UpdateRecordOperation::class);

        $mockOperation
            ->expects(self::once())
            ->method('isSuccessful')
            ->willReturn(true);

        $mappingRepository = $this->createMock(RemoteIdMappingRepository::class);

        $mappingRepository
            ->expects(self::once())
            ->method('update')
            ->with($mockOperation);

        GeneralUtility::setSingletonInstance(RemoteIdMappingRepository::class, $mappingRepository);

        $event = new RecordOperationInvocationEvent($mockOperation);

        (new MapNewUidToRemoteId())($event);
    }
}
