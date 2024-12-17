<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Exception;

/**
 * Thrown if record operations should stop. E.g. if the operation should be deferred.
 */
class StopRecordOperationException extends BeforeRecordOperationEventException
{
}
