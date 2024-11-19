<?php

declare(strict_types=1);

namespace Pixelant\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use Pixelant\Interest\DataHandling\Operation\CreateRecordOperation;
use Pixelant\Interest\DataHandling\Operation\DeleteRecordOperation;
use Pixelant\Interest\DataHandling\Operation\Event\Handler\SetContentObjectRendererLanguage;
use Pixelant\Interest\DataHandling\Operation\Event\RecordOperationSetupEvent;
use Pixelant\Interest\DataHandling\Operation\UpdateRecordOperation;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SetContentObjectRendererLanguageTest extends UnitTestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function setsNullLanguageToNull(): void
    {
        foreach (
            [
                CreateRecordOperation::class,
                UpdateRecordOperation::class,
                DeleteRecordOperation::class,
            ] as $operationClass
        ) {
            $mockContentObjectRenderer = $this->createMock(ContentObjectRenderer::class);

            $mockOperation = $this->createMock($operationClass);

            $mockOperation
                ->method('getLanguage')
                ->willReturn(null);

            $mockOperation
                ->expects(self::once())
                ->method('getContentObjectRenderer')
                ->willReturn($mockContentObjectRenderer);

            $event = new RecordOperationSetupEvent($mockOperation);

            (new SetContentObjectRendererLanguage())($event);

            // @extensionScannerIgnoreLine
            self::assertNull($mockContentObjectRenderer->data['language']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function correctlySetsHreflang(): void
    {
        foreach (
            [
                CreateRecordOperation::class,
                UpdateRecordOperation::class,
                DeleteRecordOperation::class,
            ] as $operationClass
        ) {
            $mockLanguage = $this->createMock(SiteLanguage::class);

            $mockLanguage
                ->expects(self::once())
                ->method('getHreflang')
                ->willReturn('hreflangValue');

            $mockContentObjectRenderer = $this->createMock(ContentObjectRenderer::class);

            $mockOperation = $this->createMock($operationClass);

            $mockOperation
                ->method('getLanguage')
                ->willReturn($mockLanguage);

            $mockOperation
                ->expects(self::once())
                ->method('getContentObjectRenderer')
                ->willReturn($mockContentObjectRenderer);

            $event = new RecordOperationSetupEvent($mockOperation);

            (new SetContentObjectRendererLanguage())($event);

            // @extensionScannerIgnoreLine
            self::assertEquals('hreflangValue', $mockContentObjectRenderer->data['language']);
        }
    }
}
