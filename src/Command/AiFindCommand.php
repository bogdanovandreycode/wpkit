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

final class AiFindCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('ai:find')
            ->setDescription('Find files or text in the workspace.')
            ->addArgument('query', InputArgument::REQUIRED, 'Text or filename fragment to find.')
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Path to search.', '.')
            ->addOption('name', null, InputOption::VALUE_NONE, 'Search names only.')
            ->addOption('max', 'm', InputOption::VALUE_REQUIRED, 'Max results.', 100)
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $results = (new AiFileController())->find(
                (string) $input->getArgument('query'),
                (string) $input->getOption('path'),
                (bool) $input->getOption('name'),
                (int) $input->getOption('max')
            );

            if ($input->getOption('json')) {
                $output->writeln(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } else {
                foreach ($results as $result) {
                    $line = $result['line'] === null ? '' : ':' . $result['line'];
                    $output->writeln("{$result['path']}{$line} {$result['preview']}");
                }
            }

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return Command::FAILURE;
        }
    }
}
