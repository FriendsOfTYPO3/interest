<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\DataHandler;
use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\Message\DataHandlerSuccessMessage;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\ProcessCmdmap;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationInvocationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\UpdateRecordOperation;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ProcessCmdmapTest extends UnitTestCase
{
    #[Test]
    public function returnEarlyWhenEmptyCmdmap(): void
    {
        foreach ([CreateRecordOperation::class, UpdateRecordOperation::class] as $operationClass) {
            $mockDataHandler = $this->createMock(DataHandler::class);

            $mockDataHandler
                ->expects(self::never())
                ->method('process_cmdmap');

            $mockDataHandler->cmdmap = [];

            $mockOperation = $this->createMock($operationClass);

            $mockOperation
                ->expects(self::once())
                ->method('getDataHandler')
                ->willReturn($mockDataHandler);

            $event = new RecordOperationInvocationEvent($mockOperation);

            (new ProcessCmdmap())($event);
        }
    }

    #[Test]
    public function willProcessCmdmapAndSetStatus(): void
    {
        foreach ([CreateRecordOperation::class, UpdateRecordOperation::class] as $operationClass) {
            foreach ([['iAmAnError'], []] as $errorLogKey => $errorLog) {
                $mockDataHandler = $this->createMock(DataHandler::class);

                $mockDataHandler
                    ->expects(self::once())
                    ->method('process_cmdmap');

                $mockDataHandler->cmdmap = ['iAmNotEmpty' => ['noEmptyValue' => ['123']]];

                $mockDataHandler->errorLog = $errorLog;

                $mockOperation = $this->createMock($operationClass);

                $mockOperation
                    ->method('getDataHandler')
                    ->willReturn($mockDataHandler);

                $mockOperation
                    ->expects(self::once())
                    ->method('dispatchMessage')
                    ->with(
                        self::callback(
                            function (DataHandlerSuccessMessage $message) use ($errorLogKey) {
                                self::assertEquals($message->isSuccess(), (bool)$errorLogKey);

                                return true;
                            }
                        )
                    );

                $event = new RecordOperationInvocationEvent($mockOperation);

                (new ProcessCmdmap())($event);
            }
        }
    }
}
