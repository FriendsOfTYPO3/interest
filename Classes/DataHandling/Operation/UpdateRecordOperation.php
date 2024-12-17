<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation;

use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\NotFoundException;
use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordRepresentation;
use FriendsOfTYPO3\Interest\Domain\Repository\RemoteIdMappingRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Performs an update operation on a record.
 */
class UpdateRecordOperation extends AbstractConstructiveRecordOperation
{
    public function __construct(
        RecordRepresentation $recordRepresentation,
        ?array $metaData = []
    ) {
        $remoteId = $recordRepresentation->getRecordInstanceIdentifier()->getRemoteIdWithAspects();
        if (!GeneralUtility::makeInstance(RemoteIdMappingRepository::class)->exists($remoteId)) {
            throw new NotFoundException(
                'The remote ID "' . $remoteId . '" doesn\'t exist.',
                1635780346047
            );
        }

        parent::__construct($recordRepresentation, $metaData);

        $table = $recordRepresentation->getRecordInstanceIdentifier()->getTable();

        $this->dataHandler->datamap[$table][$this->getUid()] = $this->getDataForDataHandler();
    }
}
