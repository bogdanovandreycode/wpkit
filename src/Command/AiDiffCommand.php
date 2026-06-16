<?php

declare(strict_types=1);

namespace Wpkit\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wpkit\Controller\GitStateController;

final class AiDiffCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('ai:diff')
            ->setDescription('Show git diff for the workspace or a path.')
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to diff.', '.')
            ->addOption('staged', null, InputOption::VALUE_NONE, 'Show staged diff.')
            ->addOption('summary', null, InputOption::VALUE_NONE, 'Show diff stat.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->write((new GitStateController())->diff(
                (string) $input->getArgument('path'),
                (bool) $input->getOption('staged'),
                (bool) $input->getOption('summary')
            ));

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return Command::FAILURE;
        }
    }
}
