<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\AbstractRecordOperationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface;
use FriendsOfTYPO3\Interest\Utility\RelationUtility;

/**
 * Converts relation field values from array to comma-separated string.
 */
class ConvertRelationFieldArraysToCsv implements RecordOperationEventHandlerInterface
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
                RelationUtility::reduceArrayToScalar($event->getRecordOperation()->getTable(), $fieldName, $fieldValue)
            );
        }
    }
}
