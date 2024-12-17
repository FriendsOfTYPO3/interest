<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling;

use Doctrine\DBAL\Exception\DeadlockException;
use FriendsOfTYPO3\Interest\Context;
use TYPO3\CMS\Core\DataHandling\DataHandler as Typo3DataHandler;

class DataHandler extends Typo3DataHandler
{
    /**
     * @var int
     * @see DataHandler::processClearCacheQueue()
     */
    private int $deadlockCount = 0;

    /**
     * @inheritDoc
     */
    public function updateRefIndex($table, $uid, ?int $workspace = null): void
    {
        if (Context::isDisableReferenceIndex()) {
            return;
        }

        parent::updateRefIndex($table, $uid);
    }

    /**
     * @inheritDoc
     * @throws DeadlockException
     */
    protected function processClearCacheQueue(): void
    {
        try {
            parent::processClearCacheQueue();
        } catch (DeadlockException $exception) {
            if ($this->deadlockCount > 10) {
                throw $exception;
            }

            $this->deadlockCount++;

            $this->processClearCacheQueue();
        }
    }
}
