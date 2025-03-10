<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler;

use FriendsOfTYPO3\Interest\DataHandling\Operation\AbstractConstructiveRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\AbstractRecordOperationEvent;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Exception\BeforeRecordOperationEventException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\IdentityConflictException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\UpdateRecordOperation;
use FriendsOfTYPO3\Interest\Domain\Repository\DeferredRecordOperationRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ProcessDeferredRecordOperations implements RecordOperationEventHandlerInterface
{
    public function __invoke(AbstractRecordOperationEvent $event): void
    {
        if ($event->getRecordOperation() instanceof DeleteRecordOperation) {
            return;
        }

        /** @var DeferredRecordOperationRepository $repository */
        $repository = GeneralUtility::makeInstance(DeferredRecordOperationRepository::class);

        $previousHash = '';

        foreach ($repository->get($event->getRecordOperation()->getRemoteId()) as $deferredRow) {
            $deferredRowClassParents = class_parents($deferredRow['class']);

            if (!is_array($deferredRowClassParents)) {
                $deferredRowClassParents = [];
            }

            if (
                $previousHash !== $deferredRow['_hash']
                && $deferredRow['class'] !== DeleteRecordOperation::class
                && !in_array(DeleteRecordOperation::class, $deferredRowClassParents, true)
            ) {
                $previousHash = $deferredRow['_hash'];

                try {
                    try {
                        $deferredOperation = $this->createRecordOperation(
                            $deferredRow['class'],
                            $deferredRow['arguments']
                        );
                    } catch (IdentityConflictException $exception) {
                        if (
                            $deferredRow['class'] === CreateRecordOperation::class
                            || in_array(CreateRecordOperation::class, $deferredRowClassParents, true)
                        ) {
                            $deferredOperation = $this->createRecordOperation(
                                UpdateRecordOperation::class,
                                $deferredRow['arguments']
                            );
                        } else {
                            throw $exception;
                        }
                    }

                    $deferredOperation();
                    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
                } catch (BeforeRecordOperationEventException $exception) {
                    // Ignore stop exception when processing deferred operations.
                }
            }

            $repository->delete($deferredRow['uid']);
        }
    }

    /**
     * Create a record operation of $className with $constructorArguments. This method is useful for testing, but also
     * ensures that $className is a subclass of AbstractConstructiveRecordOperation.
     *
     * @param string $className
     * @param array $constructorArguments
     * @return AbstractConstructiveRecordOperation
     *
     * @internal
     */
    public function createRecordOperation(
        string $className,
        array $constructorArguments
    ): AbstractConstructiveRecordOperation {
        return new $className(... $constructorArguments);
    }
}
