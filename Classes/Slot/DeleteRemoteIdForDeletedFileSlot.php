<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Slot;

use FriendsOfTYPO3\Interest\EventHandler\DeleteRemoteIdForDeletedFile;
use TYPO3\CMS\Core\Resource\AbstractFile;

/**
 * Delete a file's remote ID when the file is deleted.
 */
class DeleteRemoteIdForDeletedFileSlot
{
    public function __invoke(AbstractFile $file): void
    {
        DeleteRemoteIdForDeletedFile::removeRemoteIdForFile($file);
    }
}
