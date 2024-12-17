<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Unit\RequestHandler;

use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordInstanceIdentifier;
use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordRepresentation;
use FriendsOfTYPO3\Interest\RequestHandler\AbstractRecordRequestHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AbstractRecordRequestHandlerTest extends UnitTestCase
{
    #[Test]
    #[DataProvider('correctlyCompliesDataProvider')]
    public function correctlyCompliesData(array $entryPoints, array $body, array $expectedConsecutive): void
    {
        $body = json_encode($body);

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $body);
        rewind($stream);

        $request = new ServerRequest(
            'http://www.example.com/rest',
            'POST',
            $stream
        );

        $subjectMock = $this
            ->getMockBuilder(AbstractRecordRequestHandler::class)
            ->onlyMethods(['handleSingleOperation'])
            ->setConstructorArgs([
                $entryPoints,
                $request,
            ])
            ->getMock();

        $invocationCount = self::exactly(count($expectedConsecutive));

        $subjectMock
            ->expects($invocationCount)
            ->method('handleSingleOperation')
            ->willReturnCallback(function ($parameter) use ($invocationCount, $expectedConsecutive) {
                self::assertEquals($expectedConsecutive[$invocationCount->numberOfInvocations() - 1], $parameter);

                return $invocationCount->numberOfInvocations();
            });

        $subjectMock->handle();
    }

    public static function correctlyCompliesDataProvider()
    {
        return [
            'Single record' => [
                [
                    'table',
                    'remoteId',
                ],
                [
                    'data' => [
                        'title' => 'TEST',
                    ],
                ],
                [
                    new RecordRepresentation(
                        [
                            'title' => 'TEST',
                        ],
                        new RecordInstanceIdentifier(
                            'table',
                            'remoteId',
                            '',
                            ''
                        )
                    ),
                ],
            ],
            'Single record with language in query' => [
                [
                    'table',
                    'remoteId',
                    'language',
                ],
                [
                    'data' => [
                        'title' => 'TEST',
                    ],
                ],
                [
                    new RecordRepresentation(
                        [
                            'title' => 'TEST',
                        ],
                        new RecordInstanceIdentifier(
                            'table',
                            'remoteId',
                            'language',
                            ''
                        )
                    ),
                ],
            ],
            'Single record with language in data' => [
                [
                    'table',
                    'remoteId',
                ],
                [
                    'data' => [
                        'language' => [
                            'title' => 'TEST',
                        ],
                    ],
                ],
                [
                    new RecordRepresentation(
                        [
                            'title' => 'TEST',
                        ],
                        new RecordInstanceIdentifier(
                            'table',
                            'remoteId',
                            'language',
                            ''
                        )
                    ),
                ],
            ],
            'Single record with remote ID in data' => [
                [
                    'table',
                ],
                [
                    'data' => [
                        'remoteId' => [
                            'title' => 'TEST',
                        ],
                    ],
                ],
                [
                    new RecordRepresentation(
                        [
                            'title' => 'TEST',
                        ],
                        new RecordInstanceIdentifier(
                            'table',
                            'remoteId',
                            '',
                            ''
                        )
                    ),
                ],
            ],
            'Single record with remote ID and language in data' => [
                [
                    'table',
                ],
                [
                    'data' => [
                        'remoteId' => [
                            'language' => [
                                'title' => 'TEST',
                            ],
                        ],
                    ],
                ],
                [
                    new RecordRepresentation(
                        [
                            'title' => 'TEST',
                        ],
                        new RecordInstanceIdentifier(
                            'table',
                            'remoteId',
                            'language',
                            ''
                        )
                    ),
                ],
            ],
            'Single record with table and remote ID in data' => [
                [],
                [
                    'data' => [
                        'table' => [
                            'remoteId' => [
                                'title' => 'TEST',
                            ],
                        ],
                    ],
                ],
                [
                    new RecordRepresentation(
                        [
                            'title' => 'TEST',
                        ],
                        new RecordInstanceIdentifier(
                            'table',
                            'remoteId',
                            '',
                            ''
                        )
                    ),
                ],
            ],
            'Single record with table, remote ID and language in data' => [
                [],
                [
                    'data' => [
                        'table' => [
                            'remoteId' => [
                                'language' => [
                                    'title' => 'TEST',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    new RecordRepresentation(
                        [
                            'title' => 'TEST',
                        ],
                        new RecordInstanceIdentifier(
                            'table',
                            'remoteId',
                            'language',
                            ''
                        )
                    ),
                ],
            ],
            'Multiple records with same table' => [
                [
                    'table',
                ],
                [
                    'data' => [
                        'remoteId1' => [
                            'title' => 'TEST1',
                        ],
                        'remoteId2' => [
                            'title' => 'TEST2',
                        ],
                    ],
                ],
                [
                    new RecordRepresentation(
                        [
                            'title' => 'TEST1',
                        ],
                        new RecordInstanceIdentifier(
                            'table',
                            'remoteId1',
                            '',
                            ''
                        )
                    ),
                    new RecordRepresentation(
                        [
                            'title' => 'TEST2',
                        ],
                        new RecordInstanceIdentifier(
                            'table',
                            'remoteId2',
                            '',
                            ''
                        )
                    ),
                ],
            ],
            'Multiple records with same remoteId and multiple languages' => [
                [
                    'table',
                    'remoteId',
                ],
                [
                    'data' => [
                        'language1' => [
                            'title' => 'TEST1',
                        ],
                        'language2' => [
                            'title' => 'TEST2',
                        ],
                    ],
                ],
                [
                    new RecordRepresentation(
                        [
                            'title' => 'TEST1',
                        ],
                        new RecordInstanceIdentifier(
                            'table',
                            'remoteId',
                            'language1',
                            ''
                        )
                    ),
                    new RecordRepresentation(
                        [
                            'title' => 'TEST2',
                        ],
                        new RecordInstanceIdentifier(
                            'table',
                            'remoteId',
                            'language2',
                            ''
                        )
                    ),
                ],
            ],
            'Multiple records with same table and remote ID and multiple languages' => [
                [
                    'table',
                ],
                [
                    'data' => [
                        'remoteId' => [
                            'language1' => [
                                'title' => 'TEST1',
                            ],
                            'language2' => [
                                'title' => 'TEST2',
                            ],
                        ],
                    ],
                ],
                [
                    new RecordRepresentation(
                        [
                            'title' => 'TEST1',
                        ],
                        new RecordInstanceIdentifier(
                            'table',
                            'remoteId',
                            'language1',
                            ''
                        )
                    ),
                    new RecordRepresentation(
                        [
                            'title' => 'TEST2',
                        ],
                        new RecordInstanceIdentifier(
                            'table',
                            'remoteId',
                            'language2',
                            ''
                        )
                    ),
                ],
            ],
            'Single record with multiple remote IDs and multiple languages in data' => [
                [
                    'table',
                ],
                [
                    'data' => [
                        'remoteId1' => [
                            'language1' => [
                                'title' => 'TEST1',
                            ],
                            'language2' => [
                                'title' => 'TEST2',
                            ],
                        ],
                        'remoteId2' => [
                            'language3' => [
                                'title' => 'TEST1',
                            ],
                            'language4' => [
                                'title' => 'TEST2',
                            ],
                        ],

                    ],
                ],
                [
                    new RecordRepresentation(
                        [
                            'title' => 'TEST1',
                        ],
                        new RecordInstanceIdentifier(
                            'table',
                            'remoteId1',
                            'language1',
                            ''
                        )
                    ),
                    new RecordRepresentation(
                        [
                            'title' => 'TEST2',
                        ],
                        new RecordInstanceIdentifier(
                            'table',
                            'remoteId1',
                            'language2',
                            ''
                        )
                    ),
                    new RecordRepresentation(
                        [
                            'title' => 'TEST1',
                        ],
                        new RecordInstanceIdentifier(
                            'table',
                            'remoteId2',
                            'language3',
                            ''
                        )
                    ),
                    new RecordRepresentation(
                        [
                            'title' => 'TEST2',
                        ],
                        new RecordInstanceIdentifier(
                            'table',
                            'remoteId2',
                            'language4',
                            ''
                        )
                    ),
                ],
            ],
        ];
    }
}
