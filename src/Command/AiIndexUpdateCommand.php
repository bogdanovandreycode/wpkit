<?php

declare(strict_types=1);

namespace Wpkit\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wpkit\Controller\ProjectIndexController;

final class AiIndexUpdateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('ai:index-update')
            ->setDescription('Update the AI project index after code changes.')
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to scan.', '.')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Index file path.', '.wpkit/ai-index.json')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $index = (new ProjectIndexController())->build(
                (string) $input->getArgument('path'),
                (string) $input->getOption('output')
            );

            if ($input->getOption('json')) {
                $output->writeln(json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } else {
                $output->writeln("<info>AI index updated: {$index['indexPath']} ({$index['classCount']} classes)</info>");
            }

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return Command::FAILURE;
        }
    }
}
