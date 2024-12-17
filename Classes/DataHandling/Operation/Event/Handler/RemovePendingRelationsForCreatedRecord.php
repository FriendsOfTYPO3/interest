<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\AbstractRecordOperationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface;
use FriendsOfTYPO3\Interest\Domain\Repository\PendingRelationsRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * If a record has been successfully created, we can remove the pending relations records that were pointing to it. They
 * were processed earlier, but we couldn't remove them until we knew the record had been successfully created.
 *
 * @see AddResolvedPendingRelationsToDataHandler
 */
class RemovePendingRelationsForCreatedRecord implements RecordOperationEventHandlerInterface
{
    public function __invoke(AbstractRecordOperationEvent $event): void
    {
        if (
            $event->getRecordOperation()->isSuccessful()
            && $event->getRecordOperation() instanceof CreateRecordOperation
        ) {
            GeneralUtility::makeInstance(PendingRelationsRepository::class)
                ->removeRemote($event->getRecordOperation()->getRemoteId());
        }
    }
}
