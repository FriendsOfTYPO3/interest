<?php

declare(strict_types=1);

namespace Pixelant\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use PHPUnit\Framework\Attributes\Test;
use Pixelant\Interest\DataHandling\Operation\CreateRecordOperation;
use Pixelant\Interest\DataHandling\Operation\Event\Exception\StopRecordOperationException;
use Pixelant\Interest\DataHandling\Operation\Event\Handler\StopIfRepeatingPreviousRecordOperation;
use Pixelant\Interest\DataHandling\Operation\Event\RecordOperationSetupEvent;
use Pixelant\Interest\Domain\Repository\RemoteIdMappingRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class StopIfRepeatingPreviousRecordOperationTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function throwsExceptionIfSameAsPrevious(): void
    {
        $mockOperation = $this->createMock(CreateRecordOperation::class);

        $mockRepository = $this->createMock(RemoteIdMappingRepository::class);

        $mockRepository
            ->expects(self::once())
            ->method('isSameAsPrevious')
            ->with($mockOperation)
            ->willReturn(true);

        GeneralUtility::setSingletonInstance(RemoteIdMappingRepository::class, $mockRepository);

        $event = new RecordOperationSetupEvent($mockOperation);

        self::expectException(StopRecordOperationException::class);
        self::expectExceptionCode(1634567803407);

        (new StopIfRepeatingPreviousRecordOperation())($event);
    }

    #[Test]
    public function throwsNoExceptionIfDifferentToPrevious(): void
    {
        $mockOperation = $this->createMock(CreateRecordOperation::class);

        $mockRepository = $this->createMock(RemoteIdMappingRepository::class);

        $mockRepository
            ->expects(self::once())
            ->method('isSameAsPrevious')
            ->with($mockOperation)
            ->willReturn(false);

        GeneralUtility::setSingletonInstance(RemoteIdMappingRepository::class, $mockRepository);

        $event = new RecordOperationSetupEvent($mockOperation);

        (new StopIfRepeatingPreviousRecordOperation())($event);
    }
}
