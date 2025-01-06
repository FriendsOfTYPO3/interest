<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Middleware\Event;

interface HttpResponseEventHandlerInterface
{
    /**
     * Handle an HttpResponseEvent.
     *
     * @param HttpResponseEvent $event
     */
    public function __invoke(HttpResponseEvent $event): void;
}
