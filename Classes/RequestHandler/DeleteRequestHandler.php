<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\RequestHandler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordRepresentation;

class DeleteRequestHandler extends AbstractRecordRequestHandler
{
    protected const EXPECT_EMPTY_REQUEST = true;

    /**
     * @inheritDoc
     */
    protected function handleSingleOperation(
        RecordRepresentation $recordRepresentation
    ): void {
        (new DeleteRecordOperation(
            $recordRepresentation
        ))();
    }
}
