<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\AbstractRecordOperationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\Message\DataHandlerSuccessMessage;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface;

/**
 * Instructs DataHandler to process the cmdmap array.
 */
class ProcessCmdmap implements RecordOperationEventHandlerInterface
{
    public function __invoke(AbstractRecordOperationEvent $event): void
    {
        if (count($event->getRecordOperation()->getDataHandler()->cmdmap) === 0) {
            return;
        }

        $event->getRecordOperation()->getDataHandler()->process_cmdmap();

        $event->getRecordOperation()->dispatchMessage(new DataHandlerSuccessMessage(
            count($event->getRecordOperation()->getDataHandler()->errorLog) === 0
        ));
    }
}
