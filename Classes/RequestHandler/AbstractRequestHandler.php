<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\RequestHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Abstract class for handling requests.
 */
abstract class AbstractRequestHandler
{
    /**
     * @param array $entryPointParts The remaining parts of the URL, e.g. "/rest/foo/bar" makes `['foo', 'bar']`.
     * @param ServerRequestInterface $request
     */
    public function __construct(protected array $entryPointParts, protected ServerRequestInterface $request)
    {
    }

    /**
     * Handle the request.
     *
     * @return ResponseInterface
     */
    abstract public function handle(): ResponseInterface;

    /**
     * @return string[]
     */
    public function getEntryPointParts(): array
    {
        return $this->entryPointParts;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
