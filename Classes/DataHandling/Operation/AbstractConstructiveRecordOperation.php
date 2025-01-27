<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation;

use FriendsOfTYPO3\Interest\Configuration\ConfigurationProvider;
use FriendsOfTYPO3\Interest\DataHandling\DataHandler;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Exception\StopRecordOperationException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationSetupEvent;
use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordRepresentation;
use FriendsOfTYPO3\Interest\Domain\Repository\PendingRelationsRepository;
use FriendsOfTYPO3\Interest\Domain\Repository\RemoteIdMappingRepository;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract class for handling non-destructive operations. Contains a constructor that configures and populates data.
 */
abstract class AbstractConstructiveRecordOperation extends AbstractRecordOperation
{
    /**
     * @param RecordRepresentation $recordRepresentation to perform the operation on.
     * @param array|null $metaData any additional data items not to be persisted but used in processing.
     *
     * @throws StopRecordOperationException is re-thrown from BeforeRecordOperationEvent handlers
     */
    public function __construct(
        RecordRepresentation $recordRepresentation,
        ?array $metaData = []
    ) {
        $this->recordRepresentation = $recordRepresentation;
        $this->dataForDataHandler = $this->recordRepresentation->getData();
        $this->metaData = $metaData ?? [];

        $this->configurationProvider = GeneralUtility::makeInstance(ConfigurationProvider::class);

        $this->mappingRepository = GeneralUtility::makeInstance(RemoteIdMappingRepository::class);

        $this->pendingRelationsRepository = GeneralUtility::makeInstance(PendingRelationsRepository::class);

        $this->contentObjectRenderer = $this->createContentObjectRenderer();

        try {
            GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new RecordOperationSetupEvent($this));
        } catch (StopRecordOperationException $exception) {
            $this->operationStopped = true;

            throw $exception;
        }

        $this->dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $this->dataHandler->start([], []);
    }
}
