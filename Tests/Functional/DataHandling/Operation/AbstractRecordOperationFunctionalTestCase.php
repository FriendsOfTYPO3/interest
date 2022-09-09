<?php

declare(strict_types=1);

namespace Pixelant\Interest\Tests\Functional\DataHandling\Operation;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractRecordOperationFunctionalTestCase extends FunctionalTestCase
{
    /**
     * @var array<int, non-empty-string>
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/interest'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Records.csv');

        $this->setUpBackendUser(1);

        $this->setUpFrontendRootPage(1);

        GeneralUtility::setIndpEnv('TYPO3_REQUEST_URL', 'http://www.example.com/');

        $languageServiceMock = $this->createMock(LanguageService::class);

        $languageServiceMock
            ->method('sL')
            ->willReturnArgument(0);

        $GLOBALS['LANG'] = $languageServiceMock;
    }
}
