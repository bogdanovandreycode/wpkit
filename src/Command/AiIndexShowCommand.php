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

final class AiIndexShowCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('ai:index-show')
            ->setDescription('Show the AI project index or one symbol.')
            ->addArgument('symbol', InputArgument::OPTIONAL, 'Class short or full name.')
            ->addOption('index', 'i', InputOption::VALUE_REQUIRED, 'Index file path.', '.wpkit/ai-index.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = (new ProjectIndexController())->show(
                $input->getArgument('symbol') === null ? null : (string) $input->getArgument('symbol'),
                (string) $input->getOption('index')
            );

            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return Command::FAILURE;
        }
    }
}
