<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\UpdateCountOnForeignSideOfInlineRecord;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationInvocationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\UpdateRecordOperation;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class UpdateCountOnForeignSideOfInlineRecordTest extends UnitTestCase
{
    #[Test]
    public function willNotExecuteOnCreateAndUpdateOperation(): void
    {
        foreach ([CreateRecordOperation::class, UpdateRecordOperation::class] as $operationClass) {
            $mockOperation = $this->createMock($operationClass);

            $partialSubjectMock = $this->createPartialMock(
                UpdateCountOnForeignSideOfInlineRecord::class,
                ['getRecordInlineFieldRelationCount']
            );

            $partialSubjectMock
                ->expects(self::never())
                ->method('getRecordInlineFieldRelationCount');

            $event = new RecordOperationInvocationEvent($mockOperation);

            $partialSubjectMock($event);
        }
    }

    #[Test]
    public function willExecuteOnDeleteOperation(): void
    {
        $mockOperation = $this->createMock(DeleteRecordOperation::class);

        $partialSubjectMock = $this->createPartialMock(
            UpdateCountOnForeignSideOfInlineRecord::class,
            ['getRecordInlineFieldRelationCount']
        );

        $event = new RecordOperationInvocationEvent($mockOperation);

        $partialSubjectMock
            ->expects(self::once())
            ->method('getRecordInlineFieldRelationCount')
            ->with($event);

        $partialSubjectMock($event);
    }
}
