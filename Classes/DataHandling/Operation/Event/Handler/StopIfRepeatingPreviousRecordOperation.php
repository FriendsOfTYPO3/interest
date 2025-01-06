<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\AbstractRecordOperationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Exception\StopRecordOperationException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface;
use FriendsOfTYPO3\Interest\Domain\Repository\RemoteIdMappingRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class StopIfRepeatingPreviousRecordOperation implements RecordOperationEventHandlerInterface
{
    /**
     * @param AbstractRecordOperationEvent $event
     * @throws StopRecordOperationException
     */
    public function __invoke(AbstractRecordOperationEvent $event): void
    {
        /** @var RemoteIdMappingRepository $repository */
        $repository = GeneralUtility::makeInstance(RemoteIdMappingRepository::class);

        if ($repository->isSameAsPrevious($event->getRecordOperation())) {
            throw new StopRecordOperationException(
                'Operation is same as previous operation, so we can skip this.',
                1634567803407
            );
        }
    }
}
