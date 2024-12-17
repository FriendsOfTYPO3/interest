<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\RequestHandler\ExceptionConverter;

use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\AbstractException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\ConflictException as OperationConflictException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\DataHandlerErrorException as OperationDataHandlerErrorException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\IdentityConflictException as OperationIdentityConflictException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\IncompleteOperationException as OpIncompleteOperationException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\InvalidArgumentException as OperationInvalidArgumentException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\MissingArgumentException as OperationMissingArgumentException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\NotFoundException as OperationNotFoundException;
use FriendsOfTYPO3\Interest\RequestHandler\Exception\ConflictException;
use FriendsOfTYPO3\Interest\RequestHandler\Exception\DataHandlerErrorException;
use FriendsOfTYPO3\Interest\RequestHandler\Exception\IncompleteOperationException;
use FriendsOfTYPO3\Interest\RequestHandler\Exception\InvalidArgumentException;
use FriendsOfTYPO3\Interest\RequestHandler\Exception\MissingArgumentException;
use FriendsOfTYPO3\Interest\RequestHandler\Exception\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;

final class OperationToRequestHandlerExceptionConverter
{
    private const EXCEPTION_MAP = [
        OperationConflictException::class => ConflictException::class,
        OperationDataHandlerErrorException::class => DataHandlerErrorException::class,
        OperationIdentityConflictException::class => ConflictException::class,
        OpIncompleteOperationException::class => IncompleteOperationException::class,
        OperationInvalidArgumentException::class => InvalidArgumentException::class,
        OperationNotFoundException::class => NotFoundException::class,
        OperationMissingArgumentException::class => MissingArgumentException::class,
    ];

    /**
     * @param AbstractException $exception The exception to convert.
     * @param ServerRequestInterface $request The request to attach.
     * @return \Throwable
     */
    public static function convert(
        AbstractException $exception,
        ServerRequestInterface $request
    ): \Throwable {
        if (array_key_exists(get_class($exception), self::EXCEPTION_MAP)) {
            $newExceptionFqcn = self::EXCEPTION_MAP[get_class($exception)];

            return new $newExceptionFqcn(
                sprintf('%s (%s)', $exception->getMessage(), $exception->getCode()),
                $request
            );
        }

        return $exception;
    }
}
