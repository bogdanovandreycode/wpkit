<?php

declare(strict_types=1);

namespace Wpkit\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wpkit\Controller\LocaleTranslationController;

final class LanguageAddCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('language:add')
            ->setDescription('Create a translation language directory.')
            ->addArgument('locale', InputArgument::REQUIRED, 'Language locale, for example en, ro, ru.')
            ->addOption('base', 'b', InputOption::VALUE_REQUIRED, 'Translation base directory.', 'languages');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $path = (new LocaleTranslationController((string) $input->getOption('base')))
                ->addLanguage((string) $input->getArgument('locale'));

            $output->writeln("<info>Language created: {$path}</info>");

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return Command::FAILURE;
        }
    }
}
