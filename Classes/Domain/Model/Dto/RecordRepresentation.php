<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Domain\Model\Dto;

use TYPO3\CMS\Core\Schema\TcaSchema;

/**
 * DTO to handle record representation.
 */
class RecordRepresentation
{
    public function __construct(
        protected array $data,
        protected RecordInstanceIdentifier $recordInstanceIdentifier
    ) {}

    public function getData(): array
    {
        return $this->data;
    }

    public function getRecordInstanceIdentifier(): RecordInstanceIdentifier
    {
        return $this->recordInstanceIdentifier;
    }

    public function getSchema(): TcaSchema
    {
        return $this->recordInstanceIdentifier->getSchema();
    }
}
