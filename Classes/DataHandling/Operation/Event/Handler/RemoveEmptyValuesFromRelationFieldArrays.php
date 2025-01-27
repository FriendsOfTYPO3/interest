<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\AbstractRecordOperationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface;

/**
 * Removes empty values from relation arrays.
 */
class RemoveEmptyValuesFromRelationFieldArrays implements RecordOperationEventHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(AbstractRecordOperationEvent $event): void
    {
        if ($event->getRecordOperation() instanceof DeleteRecordOperation) {
            return;
        }

        foreach ($event->getRecordOperation()->getDataForDataHandler() as $fieldName => $fieldValue) {
            if (!is_array($fieldValue)) {
                continue;
            }

            $event->getRecordOperation()->setDataFieldForDataHandler(
                $fieldName,
                array_values(array_filter($fieldValue))
            );
        }
    }
}
