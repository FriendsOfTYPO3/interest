<?php

declare(strict_types=1);

namespace Pixelant\Interest\Tests\Unit\RequestHandler;

use Pixelant\Interest\RequestHandler\UpdateRequestHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class UpdateRequestHandlerTest extends UnitTestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function emptyRequestBodyWillFail(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, '');
        rewind($stream);

        $request = new ServerRequest(
            'http://www.example.com/rest',
            'PUT',
            $stream
        );

        $allClassMethods = [];
        foreach ((new \ReflectionClass(UpdateRequestHandler::class))->getMethods() as $method) {
            $allClassMethods[] = $method->getName();
        }

        $deleteHandlerMock = $this->getMockBuilder(UpdateRequestHandler::class)
            ->setConstructorArgs([
                [
                    'table',
                    'remoteId',
                ],
                $request,
            ])
            ->onlyMethods(array_diff($allClassMethods, ['handle']))
            ->getMock();

        $response = $deleteHandlerMock->handle();

        self::assertEquals(400, $response->getStatusCode());
    }
}
