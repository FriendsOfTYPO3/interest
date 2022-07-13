<?php

declare(strict_types=1);

namespace Pixelant\Interest\RequestHandler;

use Pixelant\Interest\DataHandling\Operation\DeleteRecordOperation;
use Pixelant\Interest\Domain\Model\Dto\RecordRepresentation;

class DeleteRequestHandler extends AbstractRecordRequestHandler
{
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
