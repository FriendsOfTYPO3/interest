<?php

namespace FriendsOfTYPO3\Interest\Tests\Unit\Reaction;

use FriendsOfTYPO3\Interest\Reaction\CreateUpdateDeleteReaction;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CreateUpdateDeleteReactionTest extends UnitTestCase
{
    protected ServerRequestInterface $request;

    protected function setUp(): void
    {
        parent::setUp();

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, '');
        rewind($stream);

        $this->request = new ServerRequest(
            'http://www.example.com/typo3/reaction/70e05c6e-7025-4e87-932d-dc50ba50e121',
            'POST',
            $stream
        );
    }

    #[Test]
    public function reactRoutesToCorrectActionWithPayload(): void
    {
        if (!ExtensionManagementUtility::isLoaded('reactions')) {
            self::markTestSkipped('typo3/cms-reactions not installed');

            return;
        }

        foreach (
            [
                'POST' => 'Create',
                'PUT' => 'Update',
                'PATCH' => 'CreateOrUpdate',
                'DELETE' => 'Delete',
            ] as $method => $action
        ) {
            $this->addRequestHandlerToGeneralUtility($action);

            (new CreateUpdateDeleteReaction())->react(
                $this->request,
                [
                    'method' => $method,
                ],
                // @phpstan-ignore-next-line
                $this->createMock(ReactionInstruction::class)
            );
        }
    }

    public function addRequestHandlerToGeneralUtility(string $action): void
    {
        $fqcn = 'Pixelant\\Interest\\RequestHandler\\' . $action . 'RequestHandler';

        $mock = $this->createMock($fqcn);

        $mock
            ->expects(self::once())
            ->method('handle')
            ->willReturn($this->createMock(ResponseInterface::class));

        GeneralUtility::addInstance($fqcn, $mock);
    }
}
