<?php

namespace FriendsOfTYPO3\Interest\Tests\Unit\Router;

use FriendsOfTYPO3\Interest\Router\HttpRequestRouter;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class HttpRequestRouterTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSingletonInstances = true;
    }

    #[Test]
    public function routesRequestToCorrectAction(): void
    {
        $eventDispatcherMock = $this->createMock(EventDispatcher::class);

        $eventDispatcherMock
            ->method('dispatch')
            ->willReturnArgument(0);

        GeneralUtility::setSingletonInstance(EventDispatcher::class, $eventDispatcherMock);

        foreach (
            [
                'POST' => 'Create',
                'PUT' => 'Update',
                'PATCH' => 'CreateOrUpdate',
                'DELETE' => 'Delete',
            ] as $method => $action
        ) {
            $this->addRequestHandlerToGeneralUtility($action);

            HttpRequestRouter::handleByMethod(
                $this->getRequestWithMethod($method),
                []
            );
        }
    }

    /**
     * @param string $action
     */
    public function addRequestHandlerToGeneralUtility(string $action): void
    {
        $fqcn = 'FriendsOfTYPO3\\Interest\\RequestHandler\\' . $action . 'RequestHandler';

        $mock = $this->createMock($fqcn);

        $mock
            ->expects(self::once())
            ->method('handle')
            ->willReturn($this->createMock(ResponseInterface::class));

        GeneralUtility::addInstance($fqcn, $mock);
    }

    /**
     * @param string $method
     * @return ServerRequest
     */
    protected function getRequestWithMethod(string $method): ServerRequest
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, '');
        rewind($stream);

        $request = new ServerRequest(
            'http://www.example.com/rest',
            $method,
            $stream
        );

        return $request;
    }
}
