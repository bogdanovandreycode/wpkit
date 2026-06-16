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

final class AiWriteCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('ai:write')
            ->setDescription('Write a workspace file from an argument or STDIN.')
            ->addArgument('path', InputArgument::REQUIRED, 'File path to write.')
            ->addArgument('content', InputArgument::OPTIONAL, 'Content to write. If omitted, STDIN is used.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing files.')
            ->addOption('append', 'a', InputOption::VALUE_NONE, 'Append instead of overwrite.')
            ->addOption('no-mkdir', null, InputOption::VALUE_NONE, 'Do not create missing directories.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $content = $input->getArgument('content');
            if ($content === null) {
                $content = stream_get_contents(STDIN);
            }

            $result = (new AiFileController())->write(
                (string) $input->getArgument('path'),
                (string) $content,
                (bool) $input->getOption('append'),
                (bool) $input->getOption('force'),
                !(bool) $input->getOption('no-mkdir')
            );

            if ($input->getOption('json')) {
                $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } else {
                $output->writeln("<info>{$result['mode']} {$result['path']} ({$result['bytes']} bytes)</info>");
            }

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return Command::FAILURE;
        }
    }
}
