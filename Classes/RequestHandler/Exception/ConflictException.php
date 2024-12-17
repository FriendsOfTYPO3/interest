<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\RequestHandler\Exception;

/**
 * Exception to throw if request data conflicts with existing data.
 */
class ConflictException extends AbstractRequestHandlerException
{
    protected const RESPONSE_CODE = 409;
}
