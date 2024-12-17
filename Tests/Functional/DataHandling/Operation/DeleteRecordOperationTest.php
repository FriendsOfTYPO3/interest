<?php

/**
 * @noinspection SqlNoDataSourceInspection
 * @noinspection SqlDialectInspection
 */

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Functional\DataHandling\Operation;

use PHPUnit\Framework\Attributes\Test;
use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordInstanceIdentifier;
use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordRepresentation;
use FriendsOfTYPO3\Interest\Domain\Repository\RemoteIdMappingRepository;

class DeleteRecordOperationTest extends AbstractRecordOperationFunctionalTestCase
{
    #[Test]
    public function deletingPageSetsDeletedField(): void
    {
        $mappingRepository = new RemoteIdMappingRepository();

        $mappingRepository->add('Dummy1234', 'pages', 4);

        (new DeleteRecordOperation(
            new RecordRepresentation(
                [],
                new RecordInstanceIdentifier(
                    'pages',
                    'Dummy1234'
                )
            )
        ))();

        $databaseRow = $this
            ->getConnectionPool()
            ->getConnectionForTable('pages')
            ->executeQuery('SELECT * FROM pages WHERE uid = 4')
            ->fetchAssociative();

        self::assertIsArray($databaseRow);

        self::assertEquals(1, $databaseRow['deleted']);
    }

    #[Test]
    public function deletingContentSetsDeletedField(): void
    {
        (new DeleteRecordOperation(
            new RecordRepresentation(
                [],
                new RecordInstanceIdentifier(
                    'tt_content',
                    'TranslatedContentElement'
                )
            )
        ))();

        $databaseRow = $this
            ->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->executeQuery('SELECT * FROM tt_content WHERE uid = 298')
            ->fetchAssociative();

        self::assertIsArray($databaseRow);

        self::assertEquals(1, $databaseRow['deleted']);
    }

    #[Test]
    public function deletingTranslationOfContentSetsDeletedField(): void
    {
        (new DeleteRecordOperation(
            new RecordRepresentation(
                [],
                new RecordInstanceIdentifier(
                    'tt_content',
                    'TranslatedContentElement',
                    'de'
                )
            )
        ))();

        $databaseRow = $this
            ->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->executeQuery('SELECT * FROM tt_content WHERE uid = 299')
            ->fetchAssociative();

        self::assertIsArray($databaseRow);

        self::assertEquals(1, $databaseRow['deleted']);
    }
}
