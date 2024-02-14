<?php

declare(strict_types=1);

namespace Pixelant\Interest\DataHandling\Operation\Event\Handler;

use Pixelant\Interest\DataHandling\Operation\AbstractRecordOperation;
use Pixelant\Interest\DataHandling\Operation\DeleteRecordOperation;
use Pixelant\Interest\DataHandling\Operation\Event\AbstractRecordOperationEvent;
use Pixelant\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface;
use Pixelant\Interest\Domain\Repository\RemoteIdMappingRepository;
use Pixelant\Interest\Utility\TcaUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Saves metadata information about sorting order for all MM-relation fields with >1 items. This information is used
 * when persisting the foreign side of the relation to ensure the ordering is correct, even if the foreign relations are
 * created one-by-one.
 *
 * @see FixSortingPositionsOnRemoteRelationRecords
 */
class RelationSortingAsMetaData implements RecordOperationEventHandlerInterface
{
    /**
     * @var AbstractRecordOperationEvent
     */
    protected AbstractRecordOperationEvent $event;

    /**
     * @inheritDoc
     */
    public function __invoke(AbstractRecordOperationEvent $event): void
    {
        if ($event->getRecordOperation() instanceof DeleteRecordOperation) {
            return;
        }

        $this->event = $event;

        $mmFieldConfigurations = $this->getSortedMmRelationFieldConfigurations();

        if (count($mmFieldConfigurations) === 0) {
            return;
        }

        $this->addSortingIntentToMetaData($mmFieldConfigurations);
    }

    /**
     * Returns the TCA configurations (with overrides) for the table's MM fields.
     *
     * @return array
     * @internal
     */
    public function getSortedMmRelationFieldConfigurations(): array
    {
        $recordOperation = $this->getEvent()->getRecordOperation();

        if (!isset($GLOBALS['TCA'][$recordOperation->getTable()]['columns'])) {
            return [];
        }

        $fieldConfigurations = [];
        foreach (array_keys($GLOBALS['TCA'][$recordOperation->getTable()]['columns']) as $fieldName) {
            $fieldConfiguration = $this->getTcaFieldConfigurationAndRespectColumnsOverrides(
                $recordOperation,
                $fieldName
            );

            if (
                ($fieldConfiguration['MM'] ?? '') !== ''
                && (!isset($fieldConfiguration['maxitems']) || $fieldConfiguration['maxitems'] > 1)
            ) {
                $fieldConfigurations[$fieldName] = $fieldConfiguration;
            }
        }

        return $fieldConfigurations;
    }

    /**
     * Persists the sorting intent (ordered remoteIds) to meta data.
     *
     * @param array $fieldConfigurations
     * @internal
     */
    public function addSortingIntentToMetaData(array $fieldConfigurations)
    {
        $recordOperation = $this->getEvent()->getRecordOperation();

        $sortingIntents = [];
        foreach ($fieldConfigurations as $fieldName => $configuration) {
            $sortingIntent = $recordOperation->getDataForDataHandler()[$fieldName] ?? [];

            if ($sortingIntent === []) {
                continue;
            }

            if (!is_array($sortingIntent)) {
                $sortingIntent = explode(',', $sortingIntent);
            }

            $sortingIntents[$fieldName] = $sortingIntent;
        }

        $mappingRepository = GeneralUtility::makeInstance(RemoteIdMappingRepository::class);

        $mappingRepository->setMetaDataValue(
            $recordOperation->getRemoteId(),
            self::class,
            $sortingIntents
        );
    }

    /**
     * @return AbstractRecordOperationEvent
     */
    public function getEvent(): AbstractRecordOperationEvent
    {
        return $this->event;
    }

    /**
     * Wrapper for TcaUtility::getTcaFieldConfigurationAndRespectColumnsOverrides(). Mockable in testing.
     *
     * @param AbstractRecordOperation $recordOperation
     * @param string $field
     * @return array
     * @internal
     */
    public function getTcaFieldConfigurationAndRespectColumnsOverrides(
        AbstractRecordOperation $recordOperation,
        string $field
    ): array {
        return TcaUtility::getTcaFieldConfigurationAndRespectColumnsOverrides(
            $recordOperation->getTable(),
            $field,
            $recordOperation->getDataForDataHandler(),
            $recordOperation->getRemoteId()
        );
    }
}
