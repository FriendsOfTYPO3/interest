<?php

declare(strict_types=1);


namespace Pixelant\Interest\DataHandling\Operation\Event\Handler;


use GuzzleHttp\Client;
use Pixelant\Interest\Configuration\ConfigurationProvider;
use Pixelant\Interest\DataHandling\Operation\CreateRecordOperation;
use Pixelant\Interest\DataHandling\Operation\Event\BeforeRecordOperationEvent;
use Pixelant\Interest\DataHandling\Operation\Event\BeforeRecordOperationEventHandlerInterface;
use Pixelant\Interest\DataHandling\Operation\Exception\IdentityConflictException;
use Pixelant\Interest\DataHandling\Operation\Exception\MissingArgumentException;
use Pixelant\Interest\DataHandling\Operation\Exception\NotFoundException;
use Pixelant\Interest\Domain\Repository\RemoteIdMappingRepository;
use Pixelant\Interest\Utility\CompatibilityUtility;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Intercepts a sys_file request to store the file data in the filesystem.
 */
class PersistFileDataEventHandler implements BeforeRecordOperationEventHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(BeforeRecordOperationEvent $event): void
    {
        if ($event->getRecordOperation()->getTable() !== 'sys_file') {
            return;
        }

        $data = $event->getRecordOperation()->getData();

        $fileBaseName = $data['name'];

        if (!CompatibilityUtility::getFileNameValidator()->isValid($fileBaseName)) {
            throw new InvalidFileNameException(
                'Invalid file name: "' . $fileBaseName . '"',
                1634664683340
            );
        }

        $settings = GeneralUtility::makeInstance(ConfigurationProvider::class)->getSettings();

        $storagePath = $event->getRecordOperation()->getContentObjectRenderer()->stdWrap(
            $settings['persistence.']['fileUploadFolderPath'],
            $settings['persistence.']['fileUploadFolderPath.'] ?? []
        );

        /** @var ResourceFactory $resourceFactory */
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        $storage = $resourceFactory->getStorageObjectFromCombinedIdentifier($storagePath);

        try {
            $downloadFolder = $resourceFactory->getFolderObjectFromCombinedIdentifier($storagePath);
        } catch (FolderDoesNotExistException $exception) {
            [, $folderPath] = explode(':', $storagePath);

            $downloadFolder = $storage->createFolder($folderPath);
        }

        if (get_class($event->getRecordOperation()) === CreateRecordOperation::class) {
            if ($storage->hasFileInFolder($fileBaseName, $downloadFolder)) {
                throw new IdentityConflictException(
                    'File "' . $fileBaseName . '" already exists in "' . $storagePath . '".',
                    1634666560886
                );
            }
        }

        $hashedSubfolders = (int)$event->getRecordOperation()->getContentObjectRenderer()->stdWrap(
            $settings['persistence.']['hashedSubfolders'],
            $settings['persistence.']['hashedSubfolders.'] ?? []
        );

        if ($hashedSubfolders > 0) {
            $fileNameHash = md5($fileBaseName);

            for ($i = 0; $i < $hashedSubfolders; $i++) {
                $subfolderName = substr($fileNameHash, $i, 1);

                if ($downloadFolder->hasFolder($subfolderName)) {
                    $downloadFolder = $downloadFolder->getSubfolder($subfolderName);

                    continue;
                }

                $downloadFolder = $downloadFolder->createFolder($subfolderName);
            }
        }

        if (!empty($data['fileData'])) {
            $stream = fopen('php://temp', 'rw');

            stream_filter_append($stream, 'convert.base64-decode', STREAM_FILTER_WRITE);

            $length = fwrite($stream, $data['fileData']);

            rewind($stream);

            $fileContent = fread($stream, $length);

            fclose($stream);
        } else {
            $url = $data['url'];

            if (empty($url) && get_class($event->getRecordOperation()) === CreateRecordOperation::class) {
                throw new MissingArgumentException(
                    'Cannot download file. Missing property "url" in the data.',
                    1634667221986
                );
            } elseif (!empty($url)) {
                $httpClient = GeneralUtility::makeInstance(Client::class);

                $response = $httpClient->get($url);

                if ($response->getStatusCode() >= 400) {
                    throw new NotFoundException(
                        'Request failed. URL: "' . $url . '" Reason phrase: "' . $response->getReasonPhrase() . '"',
                        1634667759711
                    );
                }

                $fileContent = $response->getBody()->getContents();
            }
        }

        /** @var RemoteIdMappingRepository $mappingRepository */
        $mappingRepository = GeneralUtility::makeInstance(RemoteIdMappingRepository::class);

        if (get_class($event->getRecordOperation()) === CreateRecordOperation::class) {
            $file = $downloadFolder->createFile($fileBaseName);
        } else {
            try {
                $file = $resourceFactory->getFileObject(
                    $mappingRepository->get($event->getRecordOperation()->getRemoteId())
                );
            } catch (FileDoesNotExistException $exception) {
                if ($mappingRepository->get($event->getRecordOperation()->getRemoteId()) === 0) {
                    throw new NotFoundException(
                        'The file with remote ID "' . $event->getRecordOperation()->getRemoteId() . '" does not '
                        . 'exist in this TYPO3 instance.',
                        1634668710602
                    );
                }

                throw new NotFoundException(
                    'The file with remote ID "' . $event->getRecordOperation()->getRemoteId() . '" and UID '
                    . '"' . $mappingRepository->get($event->getRecordOperation()->getRemoteId()) . '" does not exist.',
                    1634668857809
                );
            }
        }

        if (!empty($fileContent)) {
            $file->setContents($fileContent);
        }

        unset($data['fileData']);
        unset($data['url']);
        unset($data['name']);

        $event->getRecordOperation()->setUid($file->getUid());

        $event->getRecordOperation()->setData($data);
    }
}