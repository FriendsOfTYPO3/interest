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

    public function __serialize(): array
    {
        return [
            'data' => $this->data,
            'recordInstanceIdentifier' => $this->recordInstanceIdentifier,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->data = $data['data'] ?? $data[' * data'];
        $this->recordInstanceIdentifier = $data['recordInstanceIdentifier'] ?? $data[' * recordInstanceIdentifier'];
    }
}
