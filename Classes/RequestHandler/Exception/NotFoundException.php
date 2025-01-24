<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\RequestHandler\Exception;

/**
 * Exception to throw if data required by the request could not be found on the server.
 */
class NotFoundException extends AbstractRequestHandlerException
{
    protected const RESPONSE_CODE = 404;
}
