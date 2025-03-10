<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation;

use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\IdentityConflictException;
use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordRepresentation;
use FriendsOfTYPO3\Interest\Domain\Repository\RemoteIdMappingRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handle a record creation operation.
 */
class CreateRecordOperation extends AbstractConstructiveRecordOperation
{
    public function __construct(
        RecordRepresentation $recordRepresentation,
        ?array $metaData = []
    ) {
        $remoteId = $recordRepresentation->getRecordInstanceIdentifier()->getRemoteIdWithAspects();

        if (GeneralUtility::makeInstance(RemoteIdMappingRepository::class)->exists($remoteId)) {
            throw new IdentityConflictException(
                'The remote ID "' . $remoteId . '" already exists.',
                1635780292790
            );
        }

        parent::__construct($recordRepresentation, $metaData);

        $uid = $this->getUid();

        if ($uid === 0) {
            $uid = $this->getUidPlaceholder();
        }

        $table = $recordRepresentation->getRecordInstanceIdentifier()->getTable();

        $this->dataHandler->datamap[$table][$uid] = $this->getDataForDataHandler();
    }
}
