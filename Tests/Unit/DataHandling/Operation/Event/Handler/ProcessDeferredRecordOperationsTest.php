<?php

declare(strict_types=1);

namespace Pixelant\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\Interest\DataHandling\Operation\AbstractRecordOperation;
use Pixelant\Interest\DataHandling\Operation\CreateRecordOperation;
use Pixelant\Interest\DataHandling\Operation\DeleteRecordOperation;
use Pixelant\Interest\DataHandling\Operation\Event\Handler\PersistPendingRelationInformation;
use Pixelant\Interest\DataHandling\Operation\Event\Handler\ProcessDeferredRecordOperations;
use Pixelant\Interest\DataHandling\Operation\Event\RecordOperationInvocationEvent;
use Pixelant\Interest\DataHandling\Operation\Exception\IdentityConflictException;
use Pixelant\Interest\DataHandling\Operation\UpdateRecordOperation;
use Pixelant\Interest\Domain\Model\Dto\RecordInstanceIdentifier;
use Pixelant\Interest\Domain\Model\Dto\RecordRepresentation;
use Pixelant\Interest\Domain\Repository\DeferredRecordOperationRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ProcessDeferredRecordOperationsTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function returnEarlyIfDeleteOperation(): void
    {
        $mockOperation = $this->createMock(DeleteRecordOperation::class);

        $mockOperation
            ->expects(self::never())
            ->method('getRemoteId');

        $event = new RecordOperationInvocationEvent($mockOperation);

        (new PersistPendingRelationInformation())($event);
    }

    #[Test]
    public function deferredDeleteOperationsAreJustDeleted(): void
    {
        $deferredRecordDbRow = [
            'uid' => 123,
            'class' => DeleteRecordOperation::class,
            '_hash' => md5((string)time()),
        ];

        $mockRepository = $this->createMock(DeferredRecordOperationRepository::class);

        $mockRepository
            ->expects(self::exactly(2))
            ->method('get')
            ->with('remoteId')
            ->willReturn([$deferredRecordDbRow]);

        $mockRepository
            ->expects(self::exactly(2))
            ->method('delete')
            ->with(123);

        GeneralUtility::setSingletonInstance(DeferredRecordOperationRepository::class, $mockRepository);

        foreach ([CreateRecordOperation::class, UpdateRecordOperation::class] as $operationClass) {
            $mockOperation = $this->getMockOperation($operationClass);

            $event = new RecordOperationInvocationEvent($mockOperation);

            (new ProcessDeferredRecordOperations())($event);
        }
    }

    /**
     * @test
     *
     * @dataProvider recordOperationClassCombinationsDataProvider
     */
    public function deferredOperationsAreInvoked(string $operationClass, string $deferredOperationClass): void
    {
        $deferredRecordDbRow = $this->getDeferredRecordDbRow($deferredOperationClass);

        $this->configureMockRepository($deferredRecordDbRow);

        $mockDeferredOperation = $this->createMock($deferredOperationClass);

        $mockDeferredOperation
            ->expects(self::once())
            ->method('__invoke');

        $mockEventHandler = $this->createPartialMock(
            ProcessDeferredRecordOperations::class,
            ['createRecordOperation']
        );

        $mockEventHandler
            ->expects(self::exactly(1))
            ->method('createRecordOperation')
            ->with($deferredOperationClass, $deferredRecordDbRow['arguments'])
            ->willReturn($mockDeferredOperation);

        $event = new RecordOperationInvocationEvent($this->getMockOperation($operationClass));

        $mockEventHandler($event);
    }

    public static function recordOperationClassCombinationsDataProvider(): array
    {
        return [
            [
                CreateRecordOperation::class,
                UpdateRecordOperation::class,
            ],
            [
                UpdateRecordOperation::class,
                CreateRecordOperation::class,
            ],
            [
                CreateRecordOperation::class,
                CreateRecordOperation::class,
            ],
            [
                UpdateRecordOperation::class,
                UpdateRecordOperation::class,
            ],
        ];
    }

    #[Test]
    public function convertsDeferredCreateOperationWithConflictToUpdateOperation(): void
    {
        $deferredOperationClass = CreateRecordOperation::class;

        $deferredRecordDbRow = $this->getDeferredRecordDbRow($deferredOperationClass);

        $this->configureMockRepository($deferredRecordDbRow);

        $mockUpdateOperation = $this->createMock(UpdateRecordOperation::class);

        $mockUpdateOperation
            ->expects(self::once())
            ->method('__invoke');

        $mockEventHandler = $this->createPartialMock(
            ProcessDeferredRecordOperations::class,
            ['createRecordOperation']
        );

        $invocationCount = self::exactly(2);

        $consecutiveParameters = [
            [$deferredOperationClass, $deferredRecordDbRow['arguments']],
            [UpdateRecordOperation::class, $deferredRecordDbRow['arguments']],
        ];

        $mockEventHandler
            ->expects($invocationCount)
            ->method('createRecordOperation')
            ->willReturnCallback(
                function (
                    $parameter1,
                    $parameter2
                ) use (
                    $invocationCount,
                    $mockUpdateOperation,
                    $consecutiveParameters
                ) {
                    self::assertSame(
                        $consecutiveParameters[$invocationCount->numberOfInvocations() - 1][0],
                        $parameter1
                    );
                    self::assertSame(
                        $consecutiveParameters[$invocationCount->numberOfInvocations() - 1][1],
                        $parameter2
                    );

                    switch ($invocationCount->numberOfInvocations()) {
                        case 1:
                            throw new IdentityConflictException();
                        case 2:
                            return $mockUpdateOperation;
                    }
                }
            );

        $event = new RecordOperationInvocationEvent(
            $this->getMockOperation(CreateRecordOperation::class)
        );

        $mockEventHandler($event);
    }

    /**
     * @param array $deferredRecordDbRow
     */
    protected function configureMockRepository(array $deferredRecordDbRow): void
    {
        $mockRepository = $this->createMock(DeferredRecordOperationRepository::class);

        $mockRepository
            ->expects(self::exactly(1))
            ->method('get')
            ->with('remoteId')
            ->willReturn([$deferredRecordDbRow]);

        $mockRepository
            ->expects(self::exactly(1))
            ->method('delete')
            ->with(123);

        GeneralUtility::setSingletonInstance(
            DeferredRecordOperationRepository::class,
            $mockRepository
        );
    }

    /**
     * @param string $operationClass
     * @return MockObject|(object&MockObject)|string|AbstractRecordOperation
     */
    protected function getMockOperation(string $operationClass)
    {
        $mockOperation = $this->createMock($operationClass);

        $mockOperation
            ->expects(self::once())
            ->method('getRemoteId')
            ->willReturn('remoteId');

        return $mockOperation;
    }

    /**
     * @param string $deferredOperationClass
     * @return array
     */
    protected function getDeferredRecordDbRow(string $deferredOperationClass): array
    {
        return [
            'arguments' => [
                new RecordRepresentation(
                    [],
                    new RecordInstanceIdentifier(
                        'tablename',
                        'deferredRecordRemoteId'
                    )
                ),
                [],
            ],
            'uid' => 123,
            'class' => $deferredOperationClass,
            '_hash' => md5((string)time()),
        ];
    }
}
