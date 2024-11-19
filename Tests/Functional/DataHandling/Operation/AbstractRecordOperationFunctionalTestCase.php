<?php

declare(strict_types=1);

namespace Pixelant\Interest\Tests\Functional\DataHandling\Operation;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Settings\SettingsTypeRegistry;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Site\SiteSettingsFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractRecordOperationFunctionalTestCase extends FunctionalTestCase
{
    /**
     * @var array<non-empty-string>
     */
    protected array $testExtensionsToLoad = ['typo3conf/ext/interest'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendUser.csv');

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Records.csv');

        $this->setUpBackendUser(1);

        $this->setUpFrontendRootPage(1);

        GeneralUtility::setIndpEnv('TYPO3_REQUEST_URL', 'http://www.example.com/');

        $siteConfigurationPath = GeneralUtility::getFileAbsFileName(
            'EXT:interest/Tests/Functional/DataHandling/Operation/Fixtures/Sites'
        );

        $setRegistry = $this->createMock(SetRegistry::class);

        $packageDependentCacheIdentifier = $this->createMock(PackageDependentCacheIdentifier::class);

        $settingsTypeRegistry = new SettingsTypeRegistry($this->createMock(ServiceLocator::class));

        $siteConfiguration = new SiteConfiguration(
            $siteConfigurationPath,
            new SiteSettingsFactory(
                $siteConfigurationPath,
                $setRegistry,
                $settingsTypeRegistry,
                $this->createMock(YamlFileLoader::class),
                new NullFrontend('test'),
                $packageDependentCacheIdentifier
            ),
            new NoopEventDispatcher(),
            new NullFrontend('test'),
            new YamlFileLoader($this->createMock(LoggerInterface::class))
        );

        GeneralUtility::setSingletonInstance(
            SiteConfiguration::class,
            $siteConfiguration
        );

        $languageServiceMock = $this->createMock(LanguageService::class);

        $languageServiceMock
            ->method('sL')
            ->willReturnArgument(0);

        $GLOBALS['LANG'] = $languageServiceMock;
    }
}
