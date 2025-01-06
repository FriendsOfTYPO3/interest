<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Unit\Domain\Repository;

use FriendsOfTYPO3\Interest\Domain\Repository\PendingRelationsRepository;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PendingRelationsRepositoryTest extends UnitTestCase
{
    #[Test]
    public function emptyRemoteIdsAreIgnored(): void
    {
        $subject = $this->getMockBuilder(PendingRelationsRepository::class)
            ->onlyMethods(['getQueryBuilder', 'setSingle'])
            ->getMock();

        $subject->method('getQueryBuilder')->willReturn(
            $this->createMock(QueryBuilder::class)
        );

        $subject
            ->expects(self::exactly(1))
            ->method('setSingle')
            ->with('table1', 'field1', 1, 'remoteId1');

        $subject->set('table1', 'field1', 1, ['remoteId1', '', '']);
    }
}
