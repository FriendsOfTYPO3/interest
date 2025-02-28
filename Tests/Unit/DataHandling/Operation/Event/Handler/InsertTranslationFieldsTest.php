<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\InsertTranslationFields;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationSetupEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\UpdateRecordOperation;
use FriendsOfTYPO3\Interest\Domain\Repository\RemoteIdMappingRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Schema\FieldTypeFactory;
use TYPO3\CMS\Core\Schema\RelationMapBuilder;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class InsertTranslationFieldsTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected array $classNames = [
        CreateRecordOperation::class,
        DeleteRecordOperation::class,
        UpdateRecordOperation::class,
    ];

    #[Test]
    public function returnsEarlyIfLanguageIsNull(): void
    {
        foreach ($this->classNames as $className) {
            $mockOperation = $this->createMock($className);

            $mockOperation
                ->method('getLanguage')
                ->willReturn(null);

            $mockOperation
                ->expects(self::never())
                ->method('setDataFieldForDataHandler');

            $event = new RecordOperationSetupEvent($mockOperation);

            (new InsertTranslationFields())($event);
        }
    }

    #[Test]
    public function returnsEarlyIfLanguageIsZero(): void
    {
        foreach ($this->classNames as $className) {
            $mockLanguage = $this->createMock(SiteLanguage::class);

            $mockLanguage
                ->method('getLanguageId')
                ->willReturn(0);

            $mockOperation = $this->createMock($className);

            $mockOperation
                ->method('getLanguage')
                ->willReturn($mockLanguage);

            $mockOperation
                ->expects(self::never())
                ->method('setDataFieldForDataHandler');

            $event = new RecordOperationSetupEvent($mockOperation);

            (new InsertTranslationFields())($event);
        }
    }

    #[Test]
    public function returnsEarlyIfTableNotTranslatable(): void
    {
        foreach ($this->classNames as $className) {
            $mockOperation = $this->createMock($className);

            $mockOperation
                ->method('getLanguage')
                ->willReturn(null);

            $mockOperation
                ->method('getTable')
                ->willReturn('tablename');

            $mockOperation
                ->expects(self::never())
                ->method('setDataFieldForDataHandler');

            $event = new RecordOperationSetupEvent($mockOperation);

            (new InsertTranslationFields())($event);
        }
    }

    #[Test]
    public function returnsEarlyIfLanguageFieldIsSet(): void
    {
        foreach ($this->classNames as $className) {
            $mockOperation = $this->createMock($className);

            $mockOperation
                ->method('getLanguage')
                ->willReturn(null);

            $mockOperation
                ->method('getTable')
                ->willReturn('tablename');

            $mockOperation
                ->method('isDataFieldSet')
                ->willReturn(true);

            $mockOperation
                ->expects(self::never())
                ->method('setDataFieldForDataHandler');

            $event = new RecordOperationSetupEvent($mockOperation);

            (new InsertTranslationFields())($event);
        }
    }

    #[Test]
    #[DataProvider('provideDataForInsertsCorrectTranslationFields')]
    public function insertsCorrectTranslationFields(
        array $tca,
        array $setDataFieldForDataHandlerExpects
    ): void {
        $cacheMock = $this->createMock(PhpFrontend::class);
        $cacheMock->method('has')->with(self::isType('string'))->willReturn(false);
        $schemaFactory = new TcaSchemaFactory(
            new RelationMapBuilder($this->createMock(FlexFormTools::class)),
            new FieldTypeFactory(),
            '',
            $cacheMock
        );

        $schemaFactory->load(
            [
                'tablename' => [
                    'ctrl' => $tca,
                ]
            ],
            true
        );

        $mockLanguage = $this->createMock(SiteLanguage::class);

        $mockLanguage
            ->method('getLanguageId')
            ->willReturn(12);

        $mockMappingRepository = $this->createMock(RemoteIdMappingRepository::class);

        $mockMappingRepository
            ->method('removeAspectsFromRemoteId')
            ->willReturn('baseLanguageRemoteId');

        GeneralUtility::setSingletonInstance(RemoteIdMappingRepository::class, $mockMappingRepository);

        foreach ($this->classNames as $className) {
            $mockOperation = $this->createMock($className);

            $mockOperation
                ->method('getLanguage')
                ->willReturn($mockLanguage);

            $mockOperation
                ->method('getTable')
                ->willReturn('tablename');

            $mockOperation
                ->expects(self::exactly(count($setDataFieldForDataHandlerExpects)))
                ->method('isDataFieldSet')
                ->willReturnOnConsecutiveCalls(
                    ...array_fill(0, count($setDataFieldForDataHandlerExpects), false)
                );

            $invocationCount = self::exactly(count($setDataFieldForDataHandlerExpects));

            $mockOperation
                ->expects($invocationCount)
                ->method('setDataFieldForDataHandler')
                ->willReturnCallback(function ($parameter) use ($invocationCount, $setDataFieldForDataHandlerExpects) {
                    self::assertEquals(
                        $setDataFieldForDataHandlerExpects[$invocationCount->numberOfInvocations() - 1][0],
                        $parameter
                    );

                    return $invocationCount->numberOfInvocations();
                });

            $event = new RecordOperationSetupEvent($mockOperation);

            (new InsertTranslationFields())($event);
        }
    }

    public static function provideDataForInsertsCorrectTranslationFields(): array
    {
        return [
            [
                [
                    'languageField' => 'languageField1',
                ],
                [
                    ['languageField1', 12],
                ],
            ],
            [
                [
                    'languageField' => 'languageField2',
                    'transOrigPointerField' => 'transOrigPointerField2',
                ],
                [
                    ['languageField2', 12],
                    ['transOrigPointerField2', 'baseLanguageRemoteId'],
                ],
            ],
            [
                [
                    'languageField' => 'languageField3',
                    'transOrigPointerField' => 'transOrigPointerField3',
                    'translationSource' => 'translationSource3',
                ],
                [
                    ['languageField3', 12],
                    ['transOrigPointerField3', 'baseLanguageRemoteId'],
                    ['translationSource3', 'baseLanguageRemoteId'],
                ],
            ],
        ];
    }
}
