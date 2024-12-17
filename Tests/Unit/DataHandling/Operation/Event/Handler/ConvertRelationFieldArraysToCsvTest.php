<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use PHPUnit\Framework\Attributes\Test;
use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\ApplyFieldDataTransformations;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\ConvertRelationFieldArraysToCsv;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationSetupEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\UpdateRecordOperation;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ConvertRelationFieldArraysToCsvTest extends UnitTestCase
{
    #[Test]
    public function returnsEarlyIfDeleteRecordOperation(): void
    {
        $mockOperation = $this->createMock(DeleteRecordOperation::class);

        $mockOperation
            ->expects(self::never())
            ->method('getSettings');

        $event = new RecordOperationSetupEvent($mockOperation);

        (new ApplyFieldDataTransformations())($event);
    }

    #[Test]
    public function callsStdWrapWithCorrectArguments(): void
    {
        foreach ([CreateRecordOperation::class, UpdateRecordOperation::class] as $operationClass) {
            $mockOperation = $this->createMock($operationClass);

            $dataArray = [
                'field1' => StringUtility::getUniqueId(),
                'field2' => [StringUtility::getUniqueId(), StringUtility::getUniqueId(), StringUtility::getUniqueId()],
            ];

            $mockOperation
                ->method('getDataForDataHandler')
                ->willReturn($dataArray);

            $mockOperation
                ->expects(self::exactly(1))
                ->method('setDataFieldForDataHandler')
                ->with(
                    'field2',
                    implode(',', $dataArray['field2'])
                );

            $event = new RecordOperationSetupEvent($mockOperation);

            (new ConvertRelationFieldArraysToCsv())($event);
        }
    }
}
