<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\AbstractRecordOperationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface;
use FriendsOfTYPO3\Interest\Domain\Repository\PendingRelationsRepository;
use FriendsOfTYPO3\Interest\Utility\RelationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Adds all the pending relations to the DataHandler's datamap, so the relations are created together with the record
 * they point to.
 */
class AddResolvedPendingRelationsToDataHandler implements RecordOperationEventHandlerInterface
{
    public function __invoke(AbstractRecordOperationEvent $event): void
    {
        if (!($event->getRecordOperation() instanceof CreateRecordOperation)) {
            return;
        }

        $repository = GeneralUtility::makeInstance(PendingRelationsRepository::class);

        foreach ($repository->get($event->getRecordOperation()->getRemoteId()) as $pendingRelation) {
            RelationUtility::addResolvedPendingRelationToDataHandler(
                $event->getRecordOperation()->getDataHandler(),
                $pendingRelation,
                $event->getRecordOperation()->getTable(),
                $event->getRecordOperation()->getUidPlaceholder()
            );
        }
    }
}
