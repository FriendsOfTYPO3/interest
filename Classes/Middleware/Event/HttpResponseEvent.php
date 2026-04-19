<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Middleware\Event;

use Psr\Http\Message\ResponseInterface;

/**
 * An event created at the very end of the interest request, right before control is passed back to TYPO3.
 */
class HttpResponseEvent
{
    public function __construct(protected ResponseInterface $response)
    {
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * @param ResponseInterface $response
     */
    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }
}
