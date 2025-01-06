<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\RequestHandler\Exception;

class MissingArgumentException extends AbstractRequestHandlerException
{
    protected const RESPONSE_CODE = 404;
}
