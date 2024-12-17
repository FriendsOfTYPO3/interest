<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event;

use FriendsOfTYPO3\Interest\DataHandling\Operation\AbstractRecordOperation;

/**
 * Event called after the completion of an AbstractRecordOperation.
 */
abstract class AbstractRecordOperationEvent
{
    protected AbstractRecordOperation $recordOperation;

    /**
     * @param AbstractRecordOperation $recordOperation
     */
    public function __construct(AbstractRecordOperation $recordOperation)
    {
        $this->recordOperation = $recordOperation;
    }

    /**
     * @return AbstractRecordOperation
     */
    public function getRecordOperation(): AbstractRecordOperation
    {
        return $this->recordOperation;
    }
}
