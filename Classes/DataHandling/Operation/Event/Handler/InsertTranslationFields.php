<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\AbstractRecordOperationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface;
use FriendsOfTYPO3\Interest\Domain\Repository\RemoteIdMappingRepository;
use FriendsOfTYPO3\Interest\Utility\TcaUtility;
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

        if (
            // @extensionScannerIgnoreLine
            $recordOperation->getLanguage() === null
            || (
                // @extensionScannerIgnoreLine
                $recordOperation->getLanguage() !== null
                // @extensionScannerIgnoreLine
                && $recordOperation->getLanguage()->getLanguageId() === 0
            )
            || !$recordOperation->getRecordRepresentation()->getSchema()->isLanguageAware()
            || $recordOperation->isDataFieldSet(TcaUtility::getLanguageField($recordOperation->getTable()))
        ) {
            return;
        }

        $mappingRepository = GeneralUtility::makeInstance(RemoteIdMappingRepository::class);

        $baseLanguageRemoteId = $mappingRepository->removeAspectsFromRemoteId($recordOperation->getRemoteId());

        $recordOperation->setDataFieldForDataHandler(
            $recordOperation->getRecordRepresentation()->getSchema()->getRawConfiguration()['languageField'],
            // @extensionScannerIgnoreLine
            $recordOperation->getLanguage()->getLanguageId()
        );

        $transOrigPointerField = TcaUtility::getTransOrigPointerField($recordOperation->getTable());

        if (
            ($transOrigPointerField ?? '') !== ''
            && !$recordOperation->isDataFieldSet($transOrigPointerField)
        ) {
            $recordOperation->setDataFieldForDataHandler($transOrigPointerField, $baseLanguageRemoteId);
        }

        $translationSourceField = TcaUtility::getTranslationSourceField($recordOperation->getTable());

        if (
            ($translationSourceField ?? '') !== ''
            && !$recordOperation->isDataFieldSet($translationSourceField)
        ) {
            $recordOperation->setDataFieldForDataHandler($translationSourceField, $baseLanguageRemoteId);
        }
    }
}
