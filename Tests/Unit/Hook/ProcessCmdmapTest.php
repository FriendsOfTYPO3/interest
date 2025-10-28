<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Unit\Hook;

use FriendsOfTYPO3\Interest\DataHandling\DataHandler;
use FriendsOfTYPO3\Interest\Domain\Repository\RemoteIdMappingRepository;
use FriendsOfTYPO3\Interest\Hook\ProcessCmdmap;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ProcessCmdmapTest extends UnitTestCase
{
    #[Test]
    public function deletesRemoteIdIfOwnerRecordHasBeenDeleted(): void
    {
        $this->resetSingletonInstances = true;

        $dataHandlerMock = $this->createMock(DataHandler::class);

        $mappingRepositoryMock = $this->createMock(RemoteIdMappingRepository::class);

        $mappingRepositoryMock
            ->expects(self::exactly(1))
            ->method('remove')
            ->with('RemoteId');

        $mappingRepositoryMock
            ->expects(self::exactly(1))
            ->method('getRemoteId')
            ->with('table', 1)
            ->willReturn('RemoteId');

        GeneralUtility::setSingletonInstance(RemoteIdMappingRepository::class, $mappingRepositoryMock);

        $subjectPartialMock = $this->getMockBuilder(ProcessCmdmap::class)
            ->onlyMethods(['recordExists'])
            ->getMock();

        $subjectPartialMock
            ->expects(self::exactly(1))
            ->method('recordExists')
            ->with('table', 1)
            ->willReturn(false);

        $subjectPartialMock->processCmdmap_postProcess(
            'delete',
            'table',
            1,
            null,
            $dataHandlerMock,
            null,
            null
        );
    }

    #[Test]
    public function keepsRemoteIdIfOwnerRecordHasNotBeenDeleted(): void
    {
        $this->resetSingletonInstances = true;

        $dataHandlerMock = $this->createMock(DataHandler::class);

        $mappingRepositoryMock = $this->createMock(RemoteIdMappingRepository::class);

        $mappingRepositoryMock
            ->expects(self::never())
            ->method('remove');

        $mappingRepositoryMock
            ->expects(self::never())
            ->method('getRemoteId')
            ->with('table', 1);

        GeneralUtility::setSingletonInstance(RemoteIdMappingRepository::class, $mappingRepositoryMock);

        $subjectPartialMock = $this->getMockBuilder(ProcessCmdmap::class)
            ->onlyMethods(['recordExists'])
            ->getMock();

        $subjectPartialMock
            ->expects(self::exactly(1))
            ->method('recordExists')
            ->with('table', 1)
            ->willReturn(true);

        $subjectPartialMock->processCmdmap_postProcess(
            'delete',
            'table',
            1,
            null,
            $dataHandlerMock,
            null,
            null
        );
    }
}
