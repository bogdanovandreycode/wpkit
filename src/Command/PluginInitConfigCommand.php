<?php

namespace Wpkit\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PluginInitConfigCommand extends Command
{
    /**
     * Configure the command options and arguments.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('plugin:init-config')
            ->setDescription('Copies sample plugin-config.json into the current directory.')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Overwrite existing config file if it already exists.'
            );
    }

    /**
     * Execute the plugin config initialization command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = __DIR__ . '/../../plugin-config-sample.json';
        $destination = getcwd() . '/plugin-config.json';
        $force = $input->getOption('force');

        if (!file_exists($source)) {
            $output->writeln("<error>Sample config file not found: {$source}</error>");

            return Command::FAILURE;
        }

        if (file_exists($destination) && !$force) {
            $output->writeln("<comment>plugin-config.json already exists. Use --force to overwrite.</comment>");

            return Command::FAILURE;
        }

        if (!copy($source, $destination)) {
            $output->writeln("<error>Failed to copy config file.</error>");

            return Command::FAILURE;
        }

        $output->writeln("<info>plugin-config.json has been created in current directory.</info>");

        return Command::SUCCESS;
    }
}
