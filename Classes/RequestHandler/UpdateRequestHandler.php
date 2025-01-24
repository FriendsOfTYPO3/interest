<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\RequestHandler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\UpdateRecordOperation;
use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordRepresentation;

class UpdateRequestHandler extends AbstractRecordRequestHandler
{
    /**
     * @inheritDoc
     */
    protected function handleSingleOperation(
        RecordRepresentation $recordRepresentation
    ): void {
        (new UpdateRecordOperation(
            $recordRepresentation,
            $this->metaData
        ))();
    }
}
