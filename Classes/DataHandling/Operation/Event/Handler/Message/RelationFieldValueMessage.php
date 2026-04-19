<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\Message;

use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\RegisterValuesOfRelationFields;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Message\RequiredMessageInterface;

/**
 * The value of a foreign relation field.
 *
 * @see RegisterValuesOfRelationFields
 */
class RelationFieldValueMessage implements RequiredMessageInterface
{
    /**
     * @param string $table
     * @param string $field
     * @param int|string $id
     * @param int|string|float|array $value
     */
    public function __construct(private readonly string $table, private readonly string $field, private $id, private $value)
    {
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
     * @return int|string
     */
    public function getId()
    {
        // @extensionScannerIgnoreLine
        return $this->id;
    }

    /**
     * @return int|string|float|array
     */
    public function getValue()
    {
        return $this->value;
    }
}
