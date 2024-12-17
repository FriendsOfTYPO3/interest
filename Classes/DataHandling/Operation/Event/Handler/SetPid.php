<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\AbstractRecordOperationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface;

/**
 * Sets the 'pid' key in the data array from the storage PID, if necessary.
 */
class SetPid implements RecordOperationEventHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(AbstractRecordOperationEvent $event): void
    {
        if (
            !$event->getRecordOperation()->isDataFieldSet('pid')
            && $event->getRecordOperation() instanceof CreateRecordOperation
            && $event->getRecordOperation()->getTable() !== 'sys_file'
        ) {
            $event->getRecordOperation()->setDataFieldForDataHandler(
                'pid',
                $event->getRecordOperation()->getStoragePid()
            );
        }
    }
}
