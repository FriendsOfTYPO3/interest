<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\AbstractRecordOperationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\Message\PendingRelationMessage;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface;
use FriendsOfTYPO3\Interest\Domain\Repository\PendingRelationsRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Sets the UID in the operation if it was successful.
 */
class PersistPendingRelationInformation implements RecordOperationEventHandlerInterface
{
    public function __invoke(AbstractRecordOperationEvent $event): void
    {
        if ($event->getRecordOperation() instanceof DeleteRecordOperation) {
            return;
        }

        $repository = GeneralUtility::makeInstance(PendingRelationsRepository::class);

        do {
            /** @var PendingRelationMessage $message */
            $message = $event->getRecordOperation()->retrieveMessage(PendingRelationMessage::class);

            if ($message !== null) {
                $repository->set(
                    $message->getTable(),
                    $message->getField(),
                    $event->getRecordOperation()->getUid(),
                    $message->getRemoteIds()
                );
            }
        } while ($message !== null);
    }
}
