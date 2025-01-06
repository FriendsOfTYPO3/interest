<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Router\Event;

interface HttpRequestRouterHandleByEventHandlerInterface
{
    /**
     * Handle a HttpRequestRouterHandleByEvent.
     *
     * @param HttpRequestRouterHandleByEvent $event
     */
    public function __invoke(HttpRequestRouterHandleByEvent $event): void;
}
