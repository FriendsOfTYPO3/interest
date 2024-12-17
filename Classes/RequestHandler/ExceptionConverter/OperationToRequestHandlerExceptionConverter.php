<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\RequestHandler\ExceptionConverter;

use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\AbstractException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\ConflictException as OpConflictException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\DataHandlerErrorException as OpDataHandlerErrorException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\IdentityConflictException as OpIdentityConflictException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\IncompleteOperationException as OpIncompleteOpException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\InvalidArgumentException as OpInvalidArgumentException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\MissingArgumentException as OpMissingArgumentException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\NotFoundException as OpNotFoundException;
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
        OpConflictException::class => ConflictException::class,
        OpDataHandlerErrorException::class => DataHandlerErrorException::class,
        OpIdentityConflictException::class => ConflictException::class,
        OpIncompleteOpException::class => IncompleteOperationException::class,
        OpInvalidArgumentException::class => InvalidArgumentException::class,
        OpNotFoundException::class => NotFoundException::class,
        OpMissingArgumentException::class => MissingArgumentException::class,
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
