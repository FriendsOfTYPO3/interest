<?php

declare(strict_types=1);

namespace Pixelant\Interest\DataHandling\Operation\Event\Handler;

use Pixelant\Interest\DataHandling\Operation\Event\AbstractRecordOperationEvent;
use Pixelant\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface;
use Pixelant\Interest\DataHandling\Operation\Exception\InvalidArgumentException;

/**
 * Sets the language in the ContentObjectRenderer's data array.
 */
class SetContentObjectRendererLanguage implements RecordOperationEventHandlerInterface
{
    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     */
    public function __invoke(AbstractRecordOperationEvent $event): void
    {
        // @extensionScannerIgnoreLine
        if ($event->getRecordOperation()->getLanguage() === null) {
            // @extensionScannerIgnoreLine
            $event->getRecordOperation()->getContentObjectRenderer()->data['language'] = null;
        } else {
            // @extensionScannerIgnoreLine
            $event->getRecordOperation()->getContentObjectRenderer()->data['language']
                = $event->getRecordOperation()->getLanguage()->getHreflang();
        }
    }
}
