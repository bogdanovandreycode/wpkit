<?php

declare(strict_types=1);

namespace Wpkit\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wpkit\Controller\LintController;
use Wpkit\Controller\ProjectIndexController;

final class AiCheckCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('ai:check')
            ->setDescription('Run code checks useful before or after AI edits.')
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to check.', '.')
            ->addOption('write-index', null, InputOption::VALUE_NONE, 'Rebuild the AI project index after linting.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = (string) $input->getArgument('path');
        $ok = (new LintController())->check($path);

        if (!$ok) {
            return Command::FAILURE;
        }

        if ($input->getOption('write-index')) {
            try {
                $index = (new ProjectIndexController())->build($path);
                $output->writeln("<info>AI index updated: {$index['indexPath']} ({$index['classCount']} classes)</info>");
            } catch (Exception $exception) {
                $output->writeln("<error>Index failed: {$exception->getMessage()}</error>");

                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
