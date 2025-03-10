<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Domain\Model\Dto;

/**
 * DTO to handle record representation.
 */
class RecordRepresentation
{
    /**
     * @var array
     */
    protected array $data;

    /**
     * @var RecordInstanceIdentifier
     */
    protected RecordInstanceIdentifier $recordInstanceIdentifier;

    /**
     * @param array $data
     * @param RecordInstanceIdentifier $recordInstanceIdentifier
     */
    public function __construct(
        array $data,
        RecordInstanceIdentifier $recordInstanceIdentifier
    ) {
        $this->data = $data;
        $this->recordInstanceIdentifier = $recordInstanceIdentifier;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return RecordInstanceIdentifier
     */
    public function getRecordInstanceIdentifier(): RecordInstanceIdentifier
    {
        return $this->recordInstanceIdentifier;
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
