<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\RequestHandler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\NotFoundException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\UpdateRecordOperation;
use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordRepresentation;

class CreateOrUpdateRequestHandler extends AbstractRecordRequestHandler
{
    /**
     * @inheritDoc
     */
    protected function handleSingleOperation(
        RecordRepresentation $recordRepresentation
    ): void {
        try {
            (new UpdateRecordOperation(
                $recordRepresentation,
                $this->metaData
            ))();
        } catch (NotFoundException $exception) {
            (new CreateRecordOperation(
                $recordRepresentation,
                $this->metaData
            ))();
        }
    }
}
