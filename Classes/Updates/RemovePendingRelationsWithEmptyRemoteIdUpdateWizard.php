<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Updates;

use FriendsOfTYPO3\Interest\Domain\Repository\PendingRelationsRepository;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\ChattyInterface;

#[UpgradeWizard('interest_removePendingRelationsWithEmptyRemoteId')]
class RemovePendingRelationsWithEmptyRemoteIdUpdateWizard extends AbstractUpdateWizard implements ChattyInterface
{
    public const IDENTIFIER = 'interest_removePendingRelationsWithEmptyRemoteId';

    public const TITLE = 'Remove Invalid Pending Relations';

    public const DESCRIPTION = 'Removes pending relation records with empty remote IDs.';

    protected ?OutputInterface $output = null;

    /**
     * @inheritDoc
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * @inheritDoc
     */
    public function executeUpdate(): bool
    {
        $queryBuilder = $this->getQueryBuilderForTable(PendingRelationsRepository::TABLE_NAME);

        $deletedCount = $queryBuilder
            ->delete(PendingRelationsRepository::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('remote_id', $queryBuilder->quote(''))
            )
            ->executeStatement();

        if ($this->output !== null) {
            $this->output->writeln('Deleted ' . $deletedCount . ' pending relations with empty remote ID.');
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function updateNecessary(): bool
    {
        $queryBuilder = $this->getQueryBuilderForTable(PendingRelationsRepository::TABLE_NAME);

        return (bool)$queryBuilder
            ->count('*')
            ->from(PendingRelationsRepository::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'remote_id',
                    $queryBuilder->quote('')
                )
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @inheritDoc
     */
    public function getPrerequisites(): array
    {
        return [];
    }
}
