<?php

declare(strict_types=1);

namespace Pixelant\Interest\Tests\Unit\RequestHandler;

use PHPUnit\Framework\Attributes\Test;
use Pixelant\Interest\RequestHandler\CreateRequestHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CreateRequestHandlerTest extends UnitTestCase
{
    #[Test]
    public function emptyRequestBodyWillFail(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, '');
        rewind($stream);

        $request = new ServerRequest(
            'http://www.example.com/rest',
            'POST',
            $stream
        );

        $allClassMethods = [];
        foreach ((new \ReflectionClass(CreateRequestHandler::class))->getMethods() as $method) {
            $allClassMethods[] = $method->getName();
        }

        $deleteHandlerMock = $this->getMockBuilder(CreateRequestHandler::class)
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
