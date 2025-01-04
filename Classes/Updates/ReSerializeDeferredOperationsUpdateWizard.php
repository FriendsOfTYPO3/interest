<?php

declare(strict_types=1);

namespace Pixelant\Interest\Updates;

use Pixelant\Interest\Domain\Repository\DeferredRecordOperationRepository;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\ChattyInterface;

#[UpgradeWizard('interest_reSerializeDeferredOperations')]
class ReSerializeDeferredOperationsUpdateWizard extends AbstractUpdateWizard implements ChattyInterface
{
    public const IDENTIFIER = 'interest_reSerializeDeferredOperations';

    public const TITLE = 'Re-Serialize Deferred Operations';

    public const DESCRIPTION = 'Re-serialize deferred operations to reduce database size.';

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
        $queryBuilder = $this->getQueryBuilderForTable(DeferredRecordOperationRepository::TABLE_NAME);

        $result = $queryBuilder
            ->select('*')
            ->from(DeferredRecordOperationRepository::TABLE_NAME)
            ->executeQuery();

        $recordCount = 0;

        foreach ($result->iterateAssociative() as $row) {
            $recordCount++;
            $updatedRow = [];

            // Using @unserialize here to catch deprecation errors.
            // phpcs:ignore
            $updatedRow['arguments'] = serialize(@unserialize($row['arguments']));

            $updateQuery = $this->getQueryBuilderForTable(DeferredRecordOperationRepository::TABLE_NAME);

            $updateQuery
                ->update(DeferredRecordOperationRepository::TABLE_NAME)
                ->from(DeferredRecordOperationRepository::TABLE_NAME)
                ->where($updateQuery->expr()->eq('uid', $updateQuery->createNamedParameter($row['uid'])));

            foreach ($updatedRow as $key => $value) {
                $updateQuery->set($key, $value);
            }

            $updateQuery->executeStatement();
        }

        $this->output->writeln('Updated ' . $recordCount . ' deferred operations.');

        return true;
    }

    /**
     * @inheritDoc
     */
    public function updateNecessary(): bool
    {
        $queryBuilder = $this->getQueryBuilderForTable(DeferredRecordOperationRepository::TABLE_NAME);

        return (bool)$queryBuilder
            ->count('*')
            ->from(DeferredRecordOperationRepository::TABLE_NAME)
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
