<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\Message;

use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\MapUidsAndExtractPendingRelations;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\PersistPendingRelationInformation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Message\RequiredMessageInterface;

/**
 * A message concerning pending relations to be persisted.
 *
 * @see MapUidsAndExtractPendingRelations
 * @see PersistPendingRelationInformation
 */
class PendingRelationMessage implements RequiredMessageInterface
{
    /**
     * @param string $table
     * @param string $field
     * @param string[] $remoteIds The pointing remote IDs in a pending relation to record $uid in $field of $table.
     */
    public function __construct(
        private readonly string $table,
        private readonly string $field,
        private readonly array $remoteIds
    ) {
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return array
     */
    public function getRemoteIds(): array
    {
        return $this->remoteIds;
    }
}
