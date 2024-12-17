<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use PHPUnit\Framework\Attributes\Test;
use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\ResolveStoragePid;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationSetupEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ResolveStoragePidTest extends UnitTestCase
{
    #[Test]
    public function resolveStoragePidReturnsZeroIfRootLevelIsOne(): void
    {
        $tableName = 'testtable';

        $GLOBALS['TCA'][$tableName] = [
            'ctrl' => [
                'rootLevel' => 1,
            ],
            'columns' => [],
        ];

        $mockCreateRecordOperation = $this->createMock(CreateRecordOperation::class);

        $mockCreateRecordOperation
            ->method('getTable')
            ->willReturn($tableName);

        $mockCreateRecordOperation
            ->expects(self::once())
            ->method('setStoragePid')
            ->with(0);

        $event = new RecordOperationSetupEvent($mockCreateRecordOperation);

        (new ResolveStoragePid())($event);
    }
}
