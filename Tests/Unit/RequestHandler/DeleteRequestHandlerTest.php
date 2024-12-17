<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Tests\Unit\RequestHandler;

use FriendsOfTYPO3\Interest\RequestHandler\DeleteRequestHandler;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DeleteRequestHandlerTest extends UnitTestCase
{
    #[Test]
    public function emptyRequestBodyReturnsUnprocessableContent(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, '');
        rewind($stream);

        $request = new ServerRequest(
            'http://www.example.com/rest',
            'DELETE',
            $stream
        );

        $allClassMethods = [];
        foreach ((new \ReflectionClass(DeleteRequestHandler::class))->getMethods() as $method) {
            $allClassMethods[] = $method->getName();
        }

        $deleteHandlerMock = $this->getMockBuilder(DeleteRequestHandler::class)
            ->setConstructorArgs([
                [
                    'table',
                    'remoteId',
                ],
                $request,
            ])
            ->onlyMethods(
                array_diff(
                    $allClassMethods,
                    [
                        'handle',
                        'compileData',
                        'getEntryPointParts',
                        'formatDataArray',
                        'getRequest',
                        'handleOperations',
                    ]
                )
            )
            ->getMock();

        $response = $deleteHandlerMock->handle();

        self::assertEquals(200, $response->getStatusCode());
    }
}
