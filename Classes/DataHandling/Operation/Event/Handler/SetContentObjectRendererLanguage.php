<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\AbstractRecordOperationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\InvalidArgumentException;

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
