<?php

declare(strict_types=1);

namespace Pixelant\Interest\DataHandling\Operation\Event\Handler;

use Pixelant\Interest\DataHandling\Operation\DeleteRecordOperation;
use Pixelant\Interest\DataHandling\Operation\Event\AbstractRecordOperationEvent;
use Pixelant\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface;
use Pixelant\Interest\Utility\RelationUtility;

/**
 * Updates the inline relation record count stored in the parent record field before we delete one of the relations.
 *
 * When a parent record has an inline (IRRE) relation to a child record, TYPO3 stores the relation count (the number of
 * relations) in the parent record field used for the relation. Child relation records could e.g. be sys_file_reference
 * records.
 *
 * If we delete one of these records, the count on the parent record won't be updated. This is because TYPO3's
 * DataHandler expects the delete operation to come through a modification of the parent record.
 *
 * If the record count is higher than the actual number of relations, Extbase will trigger an exception.
 *
 * Given a DeleteRecordOperation, this  iterates through all tables and fields that potentially could have
 * a parent relationship to the record being deleted. When it finds a parent record, it will count the number of child
 * relations, subtract 1 for the record being deleted, and update the count field in the parent record. The
 * also takes record-type-related changes to a field's configuration into account.
 */
class UpdateCountOnForeignSideOfInlineRecord implements RecordOperationEventHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(AbstractRecordOperationEvent $event): void
    {
        if (!($event->getRecordOperation() instanceof DeleteRecordOperation)) {
            return;
        }

        $this->getRecordInlineFieldRelationCount($event);
    }

    /**
     * Wrapper for testing purposes.
     *
     * @param AbstractRecordOperationEvent $event
     * @internal
     */
    public function getRecordInlineFieldRelationCount(AbstractRecordOperationEvent $event): void
    {
        RelationUtility::updateParentRecordInlineFieldRelationCount(
            $event->getRecordOperation()->getTable(),
            $event->getRecordOperation()->getUid(),
            -1
        );
    }
}
