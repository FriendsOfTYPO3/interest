<?php

declare(strict_types=1);

namespace Pixelant\Interest\DataHandling\Operation\Event\Handler;

use Pixelant\Interest\DataHandling\Operation\Event\AbstractRecordOperationEvent;
use Pixelant\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface;
use Pixelant\Interest\DataHandling\Operation\Event\Handler\Message\RelationFieldValueMessage;
use Pixelant\Interest\DataHandling\Operation\UpdateRecordOperation;
use Pixelant\Interest\Utility\RelationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Process updated foreign field values to find values to delete by adding them to cmdmap.
 */
class ProcessUpdatedForeignFieldValues implements RecordOperationEventHandlerInterface
{
    public function __invoke(AbstractRecordOperationEvent $event): void
    {
        if (!($event->getRecordOperation() instanceof UpdateRecordOperation)) {
            return;
        }

        $recordOperation = $event->getRecordOperation();

        do {
            /** @var RelationFieldValueMessage $message */
            $message = $recordOperation->retrieveMessage(RelationFieldValueMessage::class);

            if ($message === null) {
                break;
            }

            $newValues = $message->getValue();

            if (!is_array($newValues)) {
                $newValues = GeneralUtility::trimExplode(',', $message->getValue(), true);
            }

            $fieldRelations = RelationUtility::getRelationsFromField(
                $message->getTable(),
                $message->getId(),
                $message->getField()
            );

            foreach ($fieldRelations as $relationTable => $relationTableValues) {
                foreach ($relationTableValues as $relationTableValue) {
                    if (!in_array((string)$relationTableValue, $newValues, true)) {
                        $recordOperation->getDataHandler()->cmdmap[$relationTable][$relationTableValue]['delete'] = 1;
                    }
                }
            }
        } while (true);
    }
}