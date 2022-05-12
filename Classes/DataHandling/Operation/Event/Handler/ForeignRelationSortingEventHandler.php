<?php

declare(strict_types=1);

namespace Pixelant\Interest\DataHandling\Operation\Event\Handler;

use Pixelant\Interest\DataHandling\DataHandler;
use Pixelant\Interest\DataHandling\Operation\DeleteRecordOperation;
use Pixelant\Interest\DataHandling\Operation\Event\AfterRecordOperationEvent;
use Pixelant\Interest\DataHandling\Operation\Event\AfterRecordOperationEventHandlerInterface;
use Pixelant\Interest\DataHandling\Operation\Exception\DataHandlerErrorException;
use Pixelant\Interest\Domain\Repository\RemoteIdMappingRepository;
use Pixelant\Interest\Utility\DatabaseUtility;
use Pixelant\Interest\Utility\TcaUtility;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Checks MM relations from a recently created record and makes sure the record has the correct order in the list of
 * items on the remote side.
 *
 * @see RelationSortingAsMetaDataEventHandler
 */
class ForeignRelationSortingEventHandler implements AfterRecordOperationEventHandlerInterface
{
    protected ?RemoteIdMappingRepository $mappingRepository = null;

    protected AfterRecordOperationEvent $event;

    /**
     * @param AfterRecordOperationEvent $event
     * @throws DataHandlerErrorException
     */
    public function __invoke(AfterRecordOperationEvent $event): void
    {
        if ($event->getRecordOperation() instanceof DeleteRecordOperation) {
            return;
        }

        $this->event = $event;

        $this->mappingRepository = GeneralUtility::makeInstance(RemoteIdMappingRepository::class);

        $data = $this->generateSortingData();

        if (count($data) > 0) {
            $this->persistData($data);
        }
    }

    /**
     * Returns the names of fields with an MM relation table.
     *
     * @return array
     */
    protected function getMmFieldConfigurations(): array
    {
        $recordOperation = $this->event->getRecordOperation();

        $persistedRecordData = DatabaseUtility::getRecord(
            $recordOperation->getTable(),
            $recordOperation->getUid()
        ) ?? $recordOperation->getData();

        $fieldConfigurations = [];
        foreach (array_keys($GLOBALS['TCA'][$recordOperation->getTable()]['columns']) as $fieldName) {
            $fieldConfiguration = TcaUtility::getTcaFieldConfigurationAndRespectColumnsOverrides(
                $recordOperation->getTable(),
                $fieldName,
                $persistedRecordData
            );

            if (!empty($fieldConfiguration['MM'] ?? '')) {
                $fieldConfigurations[$fieldName] = $fieldConfiguration;
            }
        }

        return $fieldConfigurations;
    }

    /**
     * Returns ordered relations for a single field in a record.
     *
     * A remote ID can only occur once in one record, and not in multiple fields in the same record. Therefore, this
     * method will only return an empty array (remote ID is not in a sorted field) or a single-leaf array:
     *
     * [
     *     table => [
     *         relationId => [
     *             fieldName => [ uid, ... ]
     *         ]
     *     ]
     * ]
     *
     * @param string $table The table of the record.
     * @param int $relationId The UID of the record.
     * @return array
     */
    protected function orderOnForeignSideOfRelation(string $table, int $relationId): array
    {
        $foreignRemoteId = $this->mappingRepository->getRemoteId($table, $relationId);

        if ($foreignRemoteId === false) {
            return [];
        }

        $localRemoteId = $this->event->getRecordOperation()->getRemoteId();

        $orderingIntents = $this->mappingRepository->getMetaDataValue(
            $foreignRemoteId,
            RelationSortingAsMetaDataEventHandler::class
        ) ?? [];

        $fieldName = null;
        $orderingIntent = null;

        foreach ($orderingIntents as $fieldName => $orderingIntent) {
            if (in_array($localRemoteId, $orderingIntent)) {
                break;
            }
        }

        if ($orderingIntent === null) {
            return [];
        }

        $fieldConfiguration = TcaUtility::getTcaFieldConfigurationAndRespectColumnsOverrides(
            $table,
            $fieldName,
            DatabaseUtility::getRecord($table, $relationId)
        );

        /** @var RelationHandler $relationHandler */
        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);

        $relationHandler->start(
            '',
            $fieldConfiguration['type'] === 'group'
                ? $fieldConfiguration['allowed']
                : $fieldConfiguration['foreign_table'],
            $fieldConfiguration['MM'],
            $relationId,
            $table,
            $fieldConfiguration
        );

        $relations = $relationHandler->getFromDB();

        $prefixTable = (
            $fieldConfiguration['type'] === 'group'
            && (
                $fieldConfiguration['allowed'] === '*'
                || strpos($fieldConfiguration['foreign_table'], ',') !== false
            )
        );

        $flattenedRelations = $this->flattenRelations($relations, $prefixTable);

        $orderedUids = $this->convertOrderingIntentToOrderedUids($orderingIntent, $prefixTable);

        $orderedRelations = array_merge(
            $orderedUids,
            array_diff($orderedUids, $flattenedRelations)
        );

        // Save some time by not updating correctly ordered arrays.
        if ($orderedUids === array_slice($orderedRelations, 0, count($orderedUids))) {
            return [];
        }

        return [
            $table => [
                (string)$relationId => [
                    $fieldName => $orderedRelations,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function generateSortingData(): array
    {
        $data = [];

        foreach ($this->getMmFieldConfigurations() as $fieldName => $fieldConfiguration) {
            $relationIds = $this->event->getRecordOperation()->getData()[$fieldName] ?? [];

            if (empty($relationIds)) {
                continue;
            }

            if (!is_array($relationIds)) {
                $relationIds = explode(',', (string)$relationIds);
            }

            $foreignTable = $fieldConfiguration['foreign_table'] ?? null;
            if (
                $fieldConfiguration['type'] === 'group'
                && $fieldConfiguration['allowed'] !== '*'
                && strpos($fieldConfiguration['allowed'], ',') === false
            ) {
                $foreignTable = $fieldConfiguration['allowed'];
            }

            foreach ($relationIds as $relationId) {
                if ($fieldConfiguration['type'] === 'group' && $foreignTable === null) {
                    $parts = explode('_', (string)$relationId);
                    $relationId = array_pop($parts);
                    $foreignTable = implode('_', $parts);
                }

                ArrayUtility::mergeRecursiveWithOverrule(
                    $data,
                    $this->orderOnForeignSideOfRelation($foreignTable, (int)$relationId)
                );
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @throws DataHandlerErrorException
     */
    protected function persistData(array $data): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();

        if (!empty($dataHandler->errorLog)) {
            throw new DataHandlerErrorException(
                'Error occurred during foreign-side relation ordering in remote ID based on relations'
                . ' from remote ID "' . $this->event->getRecordOperation()->getRemoteId() . '": '
                . implode(', ', $dataHandler->errorLog)
                . ' Datamap: ' . json_encode($dataHandler->datamap),
                1641480842077
            );
        }
    }

    /**
     * Provided a multidimensional relation array, this method returns a single-dimensional array of UIDs or combined
     * table_UID strings.
     *
     * @param array $relations as [tableName => [ relationRecord => [ ... ], ... ] ].
     * @param bool $prefixTable
     * @return int[]|string[]
     */
    protected function flattenRelations(array $relations, bool $prefixTable): array
    {
        $flattenedRelations = [];

        foreach ($relations as $relationTable => $relation) {
            if (!$prefixTable) {
                $flattenedRelations = array_column($relation, 'uid');

                break;
            }

            $flattenedRelations = array_map(
                function (int $item) use ($relationTable) {
                    return $relationTable . '_' . $item;
                },
                array_column($relation, 'uid')
            );
        }

        return $flattenedRelations;
    }

    /**
     * Converts a list of remote IDs to an array of UID integers or combines table_UID strings.
     *
     * @param array $orderingIntent
     * @param bool $prefixTable
     * @return array
     */
    protected function convertOrderingIntentToOrderedUids(array $orderingIntent, bool $prefixTable): array
    {
        $orderedUids = [];
        foreach ($orderingIntent as $remoteIdToOrder) {
            $uid = $this->mappingRepository->get($remoteIdToOrder);

            if ($uid === 0) {
                continue;
            }

            if (!$prefixTable) {
                $orderedUids[] = $uid;

                continue;
            }

            $orderedUids[] = $this->mappingRepository->table($remoteIdToOrder) . '_' . $uid;
        }

        return $orderedUids;
    }
}
