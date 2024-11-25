<?php

declare(strict_types=1);

namespace Pixelant\Interest\Tests\Unit\DataHandling\Operation\Event\Handler;

use PHPUnit\Framework\Attributes\Test;
use Pixelant\Interest\DataHandling\Operation\CreateRecordOperation;
use Pixelant\Interest\DataHandling\Operation\DeleteRecordOperation;
use Pixelant\Interest\DataHandling\Operation\Event\Handler\RemoveEmptyValuesFromRelationFieldArrays;
use Pixelant\Interest\DataHandling\Operation\Event\RecordOperationSetupEvent;
use Pixelant\Interest\DataHandling\Operation\UpdateRecordOperation;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RemoveEmptyValuesFromRelationFieldArraysTest extends UnitTestCase
{
    #[Test]
    public function returnEarlyIfDeleteOperation(): void
    {
        $mockOperation = $this->createMock(DeleteRecordOperation::class);

        $mockOperation
            ->expects(self::never())
            ->method('getDataForDataHandler');

        $event = new RecordOperationSetupEvent($mockOperation);

        (new RemoveEmptyValuesFromRelationFieldArrays())($event);
    }

    #[Test]
    public function correctlyRemovesEmptyValuesFromRelationArrays(): void
    {
        $dataForDataHandler = [
            'nonRelationField' => 'nonRelationFieldValue',
            'emptyRelationField' => [],
            'relationFieldWithNoEmptyValues' => ['remoteId1', 'remoteId2', 'remoteId3'],
            'relationFieldWithSomeEmptyValues' => ['remoteId4', null, 0, false, '0', '', 'remoteId5', 'remoteId6'],
            'relationFieldWithOnlyEmptyValues' => [null, 0, false, '0', ''],
        ];

        $expectedSetDataFieldForDataHandlerArguments = [
            ['emptyRelationField', []],
            ['relationFieldWithNoEmptyValues', ['remoteId1', 'remoteId2', 'remoteId3']],
            ['relationFieldWithSomeEmptyValues', ['remoteId4', 'remoteId5', 'remoteId6']],
            ['relationFieldWithOnlyEmptyValues', []],
        ];

        foreach ([CreateRecordOperation::class, UpdateRecordOperation::class] as $operationClass) {
            $mockOperation = $this->createMock($operationClass);

            $mockOperation
                ->expects(self::once())
                ->method('getDataForDataHandler')
                ->willReturn($dataForDataHandler);

            $invocationCount = self::exactly(count($expectedSetDataFieldForDataHandlerArguments));

            $mockOperation
                ->expects($invocationCount)
                ->method('setDataFieldForDataHandler')
                ->willReturnCallback(
                    function (
                        $parameter1,
                        $parameter2,
                    ) use (
                        $invocationCount,
                        $expectedSetDataFieldForDataHandlerArguments
                    ) {
                        $i = $invocationCount->numberOfInvocations() - 1;

                        self::assertEquals($expectedSetDataFieldForDataHandlerArguments[$i][0], $parameter1);
                        self::assertEquals($expectedSetDataFieldForDataHandlerArguments[$i][1], $parameter2);

                        return $invocationCount->numberOfInvocations();
                    }
                );

            $event = new RecordOperationSetupEvent($mockOperation);

            (new RemoveEmptyValuesFromRelationFieldArrays())($event);
        }
    }
}
