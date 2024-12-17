<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\Domain\Repository\RemoteIdMappingRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Defers a record where the remote ID for a PID doesn't exist yet.
 */
class DeferIfPidDoesNotYetExist extends AbstractDetermineDeferredRecordOperation
{
    protected function getDependentRemoteId(): ?string
    {
        $mappingRepository = GeneralUtility::makeInstance(RemoteIdMappingRepository::class);

        $pid = $this->getEvent()->getRecordOperation()->getDataForDataHandler()['pid'][0] ?? null;

        if ($pid !== null && $mappingRepository->exists($pid)) {
            return null;
        }

        return $pid;
    }
}
