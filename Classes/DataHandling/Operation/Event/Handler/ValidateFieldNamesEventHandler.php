<?php

declare(strict_types=1);

namespace Pixelant\Interest\DataHandling\Operation\Event\Handler;

use Pixelant\Interest\DataHandling\Operation\DeleteRecordOperation;
use Pixelant\Interest\DataHandling\Operation\Event\BeforeRecordOperationEvent;
use Pixelant\Interest\DataHandling\Operation\Event\BeforeRecordOperationEventHandlerInterface;
use Pixelant\Interest\DataHandling\Operation\Exception\ConflictException;

/**
 * Attempts to resolve the storage PID.
 */
class ValidateFieldNamesEventHandler implements BeforeRecordOperationEventHandlerInterface
{
    /**
     * @inheritDoc
     * @throws ConflictException
     */
    public function __invoke(BeforeRecordOperationEvent $event): void
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
