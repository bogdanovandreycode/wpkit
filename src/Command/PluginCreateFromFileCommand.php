<?php

namespace Wpkit\Command;

use Exception;
use Wpkit\Model\ArgumentModel;
use Wpkit\Controller\PluginGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PluginCreateFromFileCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('plugin:create-from-file')
            ->setDescription('Creates a new WordPress plugin from a JSON configuration file.')
            ->addArgument(
                'config',
                InputArgument::REQUIRED,
                'Path to the JSON configuration file.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configPath = $input->getArgument('config');

        if (!file_exists($configPath)) {
            $output->writeln("<error>Config file not found: {$configPath}</error>");
            return Command::FAILURE;
        }

        $json = file_get_contents($configPath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $output->writeln("<error>Invalid JSON: " . json_last_error_msg() . "</error>");
            return Command::FAILURE;
        }

        $arguments = [];
        foreach ($data as $key => $value) {
            $arguments[] = new ArgumentModel($key, '', false, $value);
        }

        try {
            $generator = new PluginGenerator($arguments);
            $generator->generate();
            $output->writeln("<info>Plugin successfully created from file.</info>");
            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
