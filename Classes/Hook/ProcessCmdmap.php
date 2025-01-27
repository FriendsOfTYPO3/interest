<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Hook;

use FriendsOfTYPO3\Interest\Domain\Repository\RemoteIdMappingRepository;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Ensure remote ID entry is deleted if the record for the remote ID is deleted.
 */
class ProcessCmdmap
{
    /**
     * @param string $command
     * @param string $table
     * @param $id
     * @param $value
     * @param DataHandler $dataHandler
     * @param $pasteUpdate
     * @param $pasteDatamap
     *
     * phpcs:disable PSR1.Methods.CamelCapsMethodName
     */
    public function processCmdmap_postProcess(
        string $command,
        string $table,
        $id,
        $value,
        DataHandler $dataHandler,
        $pasteUpdate,
        $pasteDatamap
    ): void {
        if ($command === 'delete' && $dataHandler->hasDeletedRecord($table, $id)) {
            /** @var RemoteIdMappingRepository $mappingRepository */
            $mappingRepository = GeneralUtility::makeInstance(RemoteIdMappingRepository::class);

            $remoteId = $mappingRepository->getRemoteId($table, $id);

            if ($remoteId !== false) {
                $mappingRepository->remove($remoteId);
            }
        }
    }
}
