<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Command;

use FriendsOfTYPO3\Interest\DataHandling\Operation\CreateRecordOperation;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Exception\StopRecordOperationException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\NotFoundException;
use FriendsOfTYPO3\Interest\DataHandling\Operation\UpdateRecordOperation;
use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordInstanceIdentifier;
use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordRepresentation;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for updating a record.
 */
class UpdateCommandController extends AbstractReceiveCommandController
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Update a record.')
            ->addOption(
                'create',
                'c',
                InputOption::VALUE_NONE,
                'Quietly create the record if it doesn\'t already exist.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @throws NotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exceptions = [];

        foreach ($input->getOption('data') as $remoteId => $data) {
            try {
                (new UpdateRecordOperation(
                    new RecordRepresentation(
                        $data,
                        new RecordInstanceIdentifier(
                            $input->getArgument('endpoint'),
                            $remoteId,
                            (string)$input->getArgument('language'),
                            (string)$input->getArgument('workspace'),
                        )
                    ),
                    $input->getOption('metaData')
                ))();
            } catch (StopRecordOperationException $exception) {
                $output->writeln($exception->getMessage(), OutputInterface::VERBOSITY_VERY_VERBOSE);

                continue;
            } catch (NotFoundException $exception) {
                if ($input->getOption('create') === false) {
                    throw $exception;
                }

                try {
                    (new CreateRecordOperation(
                        new RecordRepresentation(
                            $data,
                            new RecordInstanceIdentifier(
                                $input->getArgument('endpoint'),
                                $remoteId,
                                (string)$input->getArgument('language'),
                                (string)$input->getArgument('workspace'),
                            )
                        ),
                        $input->getOption('metaData')
                    ))();
                } catch (StopRecordOperationException $exception) {
                    $output->writeln($exception->getMessage(), OutputInterface::VERBOSITY_VERY_VERBOSE);

                    continue;
                }
            } catch (\Throwable $exception) {
                $exceptions[] = $exception;
            }
        }

        if (count($exceptions) > 0) {
            foreach ($exceptions as $exception) {
                $this->getApplication()->renderThrowable($exception, $output);
            }

            return 255;
        }

        return 0;
    }
}
