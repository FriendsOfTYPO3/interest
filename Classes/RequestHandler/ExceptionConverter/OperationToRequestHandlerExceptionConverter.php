<?php

declare(strict_types=1);

namespace Pixelant\Interest\RequestHandler\ExceptionConverter;

use Pixelant\Interest\DataHandling\Operation\Exception\AbstractException;
use Pixelant\Interest\DataHandling\Operation\Exception\ConflictException as OperationConflictException;
use Pixelant\Interest\DataHandling\Operation\Exception\DataHandlerErrorException as OperationDataHandlerErrorException;
use Pixelant\Interest\DataHandling\Operation\Exception\IdentityConflictException as OperationIdentityConflictException;
use Pixelant\Interest\DataHandling\Operation\Exception\IncompleteOperationException as OpIncompleteOperationException;
use Pixelant\Interest\DataHandling\Operation\Exception\InvalidArgumentException as OperationInvalidArgumentException;
use Pixelant\Interest\DataHandling\Operation\Exception\MissingArgumentException as OperationMissingArgumentException;
use Pixelant\Interest\DataHandling\Operation\Exception\NotFoundException as OperationNotFoundException;
use Pixelant\Interest\RequestHandler\Exception\ConflictException;
use Pixelant\Interest\RequestHandler\Exception\DataHandlerErrorException;
use Pixelant\Interest\RequestHandler\Exception\IncompleteOperationException;
use Pixelant\Interest\RequestHandler\Exception\InvalidArgumentException;
use Pixelant\Interest\RequestHandler\Exception\MissingArgumentException;
use Pixelant\Interest\RequestHandler\Exception\NotFoundException;
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
