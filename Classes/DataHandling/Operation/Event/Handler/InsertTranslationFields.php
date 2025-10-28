<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\AbstractRecordOperationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface;
use FriendsOfTYPO3\Interest\Domain\Repository\RemoteIdMappingRepository;
use FriendsOfTYPO3\Interest\Utility\TcaUtility;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Create the translation fields if the table is translatable, language is set and nonzero, and the language field
 * hasn't already been set.
 */
class InsertTranslationFields implements RecordOperationEventHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(AbstractRecordOperationEvent $event): void
    {
        $recordOperation = $event->getRecordOperation();

        $recordSchema = $recordOperation->getRecordRepresentation()->getSchema();

        if (!$recordSchema->hasCapability(TcaSchemaCapability::Language)) {
            return;
        }

        $languageCapability = $recordSchema->getCapability(TcaSchemaCapability::Language);

        if (
            // @extensionScannerIgnoreLine
            $recordOperation->getLanguage() === null
            || (
                // @extensionScannerIgnoreLine
                $recordOperation->getLanguage() !== null
                // @extensionScannerIgnoreLine
                && $recordOperation->getLanguage()->getLanguageId() === 0
            )
            || $recordOperation->isDataFieldSet($languageCapability->getLanguageField()->getName())
        ) {
            return;
        }

        $mappingRepository = GeneralUtility::makeInstance(RemoteIdMappingRepository::class);

        $baseLanguageRemoteId = $mappingRepository->removeAspectsFromRemoteId($recordOperation->getRemoteId());

        $recordOperation->setDataFieldForDataHandler(
            $languageCapability->getLanguageField()->getName(),
            // @extensionScannerIgnoreLine
            $recordOperation->getLanguage()->getLanguageId()
        );

        $transOriginPointerField = $languageCapability->getTranslationOriginPointerField()->getName();

        if (
            ($transOriginPointerField ?? '') !== ''
            && !$recordOperation->isDataFieldSet($transOriginPointerField)
        ) {
            $recordOperation->setDataFieldForDataHandler($transOriginPointerField, $baseLanguageRemoteId);
        }

         if (
            $languageCapability->hasTranslationSourceField()
            && !$recordOperation->isDataFieldSet($languageCapability->getTranslationSourceField()->getName())
        ) {
            $recordOperation->setDataFieldForDataHandler(
                $languageCapability->getTranslationSourceField()->getName(),
                $baseLanguageRemoteId
            );
        }
    }
}
