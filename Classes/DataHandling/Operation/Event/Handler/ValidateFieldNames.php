<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\AbstractRecordOperationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\ConflictException;

/**
 * Attempts to resolve the storage PID.
 */
class ValidateFieldNames implements RecordOperationEventHandlerInterface
{
    /**
     * @inheritDoc
     * @throws ConflictException
     */
    public function __invoke(AbstractRecordOperationEvent $event): void
    {
        if ($event->getRecordOperation() instanceof DeleteRecordOperation) {
            return;
        }

        $fieldsNotInTca = array_diff_key(
            $event->getRecordOperation()->getDataForDataHandler(),
            $GLOBALS['TCA'][$event->getRecordOperation()->getTable()]['columns'] ?? []
        );

        if (count(array_diff(array_keys($fieldsNotInTca), ['pid'])) > 0) {
            throw new ConflictException(
                'Unknown field(s) in field list: ' . implode(', ', array_keys($fieldsNotInTca)),
                1634119601036
            );
        }
    }
}
