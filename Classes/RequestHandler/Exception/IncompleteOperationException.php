<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\RequestHandler\Exception;

/**
 * @see \FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\IncompleteOperationException
 */
class IncompleteOperationException extends AbstractRequestHandlerException
{
    protected const RESPONSE_CODE = 400;
}
