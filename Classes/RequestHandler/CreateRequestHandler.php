<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\RequestHandler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordRepresentation;

class CreateRequestHandler extends AbstractRecordRequestHandler
{
    /**
     * @inheritDoc
     */
    protected function handleSingleOperation(
        RecordRepresentation $recordRepresentation
    ): void {
        (new CreateRecordOperation(
            $recordRepresentation,
            $this->metaData
        ))();
    }
}
