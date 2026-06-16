<?php

declare(strict_types=1);

namespace Wpkit\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wpkit\Controller\AiFileController;

final class AiReadCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('ai:read')
            ->setDescription('Read a workspace file with optional line slicing.')
            ->addArgument('path', InputArgument::REQUIRED, 'File path to read.')
            ->addOption('start', 's', InputOption::VALUE_REQUIRED, 'Start line.', 1)
            ->addOption('lines', 'l', InputOption::VALUE_REQUIRED, 'Number of lines to read.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = (new AiFileController())->read(
                (string) $input->getArgument('path'),
                (int) $input->getOption('start'),
                $input->getOption('lines') === null ? null : (int) $input->getOption('lines')
            );

            if ($input->getOption('json')) {
                $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } else {
                $output->writeln("<info>{$result['path']}:{$result['start']}-{$result['end']} of {$result['totalLines']}</info>");
                $output->writeln($result['content']);
            }

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return Command::FAILURE;
        }
    }
}
