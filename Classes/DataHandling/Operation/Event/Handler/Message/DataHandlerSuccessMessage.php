<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\Message;

use FriendsOfTYPO3\Interest\DataHandling\Operation\AbstractRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Message\ReplacesPreviousMessageInterface;

/**
 * A message about a failed or successful DataHandler operation.
 *
 * @see AbstractRecordOperation::isSuccessful()
 * @see AbstractRecordOperation::hasExecuted()
 */
class DataHandlerSuccessMessage implements ReplacesPreviousMessageInterface
{
    private bool $success;

    /**
     * @param bool $success
     */
    public function __construct(bool $success)
    {
        $this->success = $success;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }
}
