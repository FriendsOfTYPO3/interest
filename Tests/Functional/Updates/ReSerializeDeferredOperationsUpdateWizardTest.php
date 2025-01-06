<?php

declare(strict_types=1);

namespace Pixelant\Interest\Tests\Functional\Updates;

use PHPUnit\Framework\Attributes\Test;
use Pixelant\Interest\Domain\Model\Dto\RecordInstanceIdentifier;
use Pixelant\Interest\Domain\Model\Dto\RecordRepresentation;
use Pixelant\Interest\Domain\Repository\DeferredRecordOperationRepository;
use Pixelant\Interest\Tests\Functional\SiteBasedTestTrait;
use Pixelant\Interest\Updates\ReSerializeDeferredOperationsUpdateWizard;
use Symfony\Component\Console\Output\NullOutput;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ReSerializeDeferredOperationsUpdateWizardTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'en' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF-8'],
    ];

    protected array $testExtensionsToLoad = ['typo3conf/ext/interest'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/DeferredOperations.csv');

        $this->writeSiteConfiguration(
            'main',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('en', '/'),
            ]
        );
    }

    #[Test]
    public function deferredOperationsAreReNamespacedAndUpdatedTest()
    {
        $updateWizard = GeneralUtility::makeInstance(ReSerializeDeferredOperationsUpdateWizard::class);

        $updateWizard->setOutput(new NullOutput());

        self::assertTrue($updateWizard->updateNecessary());

        self::assertTrue($updateWizard->executeUpdate());

        $deferredOperationsRepository = GeneralUtility::makeInstance(DeferredRecordOperationRepository::class);

        $deferredOperations = $deferredOperationsRepository->get('dependentRemoteId');

        foreach ($deferredOperations as $row) {
            $arguments = $row['arguments'];

            self::assertTrue(
                $arguments[0] instanceof RecordRepresentation,
                'Deferred operation is RecordRepresentation.'
            );

            self::assertTrue(
                $arguments[0]->getRecordInstanceIdentifier() instanceof RecordInstanceIdentifier,
                'Deferred operation has RecordInstanceIdentifier.'
            );

            self::assertEquals(
                [
                    'title' => 'page title',
                ],
                $arguments[0]->getData()
            );

            self::assertEquals(
                'pages',
                $arguments[0]->getRecordInstanceIdentifier()->getTable()
            );

            self::assertEquals(
                'myRemoteId',
                $arguments[0]->getRecordInstanceIdentifier()->getRemoteId()
            );

            self::assertEquals(
                'en-US',
                $arguments[0]->getRecordInstanceIdentifier()->getLanguage()->getLocale()->getName()
            );

            self::assertEquals(
                0,
                $arguments[0]->getRecordInstanceIdentifier()->getUid()
            );
        }
    }
}
