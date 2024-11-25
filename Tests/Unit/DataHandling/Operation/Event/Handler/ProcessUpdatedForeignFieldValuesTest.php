<?php

declare(strict_types=1);

namespace Pixelant\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use PHPUnit\Framework\Attributes\Test;
use Pixelant\Interest\DataHandling\DataHandler;
use Pixelant\Interest\DataHandling\Operation\CreateRecordOperation;
use Pixelant\Interest\DataHandling\Operation\DeleteRecordOperation;
use Pixelant\Interest\DataHandling\Operation\Event\Handler\Message\RelationFieldValueMessage;
use Pixelant\Interest\DataHandling\Operation\Event\Handler\ProcessUpdatedForeignFieldValues;
use Pixelant\Interest\DataHandling\Operation\Event\RecordOperationInvocationEvent;
use Pixelant\Interest\DataHandling\Operation\UpdateRecordOperation;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ProcessUpdatedForeignFieldValuesTest extends UnitTestCase
{
    #[Test]
    public function returnEarlyWhenNotUpdateOperation(): void
    {
        foreach ([DeleteRecordOperation::class, CreateRecordOperation::class] as $operationClass) {
            $mockOperation = $this->createMock($operationClass);

            $mockOperation
                ->expects(self::never())
                ->method('retrieveMessage');

            $event = new RecordOperationInvocationEvent($mockOperation);

            (new ProcessUpdatedForeignFieldValues())($event);
        }
    }

    #[Test]
    public function processWhenUpdateOperationAndReturnWhenNoMessages(): void
    {
        $mockOperation = $this->createMock(UpdateRecordOperation::class);

        $mockOperation
            ->expects(self::once())
            ->method('retrieveMessage')
            ->willReturn(null);

        $event = new RecordOperationInvocationEvent($mockOperation);

        (new ProcessUpdatedForeignFieldValues())($event);
    }

    #[Test]
    public function correctlySetsCmdmap(): void
    {
        $messageValues = [
            ['tablename1', 'firstField', 123, [1, 2]],
            ['tablename2', 'firstField', 456, '4,6'],
            ['tablename3', 'secondField', 789, [7, 8, 9]],
        ];

        $relationReturns = [
            [
                'relationTableA' => [1, 2, 3],
                'relationTableB' => [4, 5, 6, 10],
            ],
            [
                'relationTableA' => [4, 5, 6],
            ],
            [
                'relationTableA' => [7, 8, 9],
                'relationTableB' => [4, 5, 6, 10],
            ],
        ];

        // Though technically correct, this array also illustrates that there is nothing preventing a record from being
        // deleted while it still has valid relations. There's currently no good/performant way to gather this
        // information, so in real life we have to assume that the RelationFieldValueMessage was generated sanely.
        $expectedCmdmap = [
            'relationTableA' => [
                1 => ['delete' => 1],
                2 => ['delete' => 1],
                3 => ['delete' => 1],
                5 => ['delete' => 1],
                7 => ['delete' => 1],
                8 => ['delete' => 1],
                9 => ['delete' => 1],
            ],
            'relationTableB' => [
                4 => ['delete' => 1],
                5 => ['delete' => 1],
                6 => ['delete' => 1],
                10 => ['delete' => 1],
            ],
        ];

        $mockDataHandler = $this->createMock(DataHandler::class);

        $mockDataHandler->cmdmap = [];

        $mockOperation = $this->createMock(UpdateRecordOperation::class);

        $expectedParameters = [];

        foreach ($messageValues as $messageValueSet) {
            $expectedParameters[] = new RelationFieldValueMessage(... $messageValueSet);
        }

        $mockOperation
            ->expects(self::exactly(count($expectedParameters) + 1))
            ->method('retrieveMessage')
            ->with(RelationFieldValueMessage::class)
            ->willReturnOnConsecutiveCalls(... [... $expectedParameters, null]);

        $mockOperation
            ->method('getDataHandler')
            ->willReturn($mockDataHandler);

        $partialMockEventHandler = $this->createPartialMock(
            ProcessUpdatedForeignFieldValues::class,
            ['getRelationsFromMessage']
        );

        $invocationCount = self::exactly(count($messageValues));

        $partialMockEventHandler
            ->expects($invocationCount)
            ->method('getRelationsFromMessage')
            ->willReturnCallback(
                function ($parameters) use ($invocationCount, $expectedParameters, $relationReturns) {
                    self::assertEquals($expectedParameters[$invocationCount->numberOfInvocations() - 1], $parameters);

                    return $relationReturns[$invocationCount->numberOfInvocations() - 1];
                }
            );

        $event = new RecordOperationInvocationEvent($mockOperation);

        $partialMockEventHandler($event);

        self::assertEquals($expectedCmdmap, $mockDataHandler->cmdmap);
    }
}
