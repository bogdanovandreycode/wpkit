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

final class LocaleAddCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('locale:add')
            ->setAliases(['translation:add'])
            ->setDescription('Add a translation value for a locale.')
            ->addArgument('locale', InputArgument::REQUIRED, 'Language locale, for example en, ro, ru.')
            ->addArgument('address', InputArgument::REQUIRED, 'Translation address, for example payment.maib_payment.')
            ->addArgument('value', InputArgument::REQUIRED, 'Translation value.')
            ->addOption('base', 'b', InputOption::VALUE_REQUIRED, 'Translation base directory.', 'languages')
            ->addOption('dictionary', 'd', InputOption::VALUE_REQUIRED, 'Dictionary path when address is only a key.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $file = (new LocaleTranslationController((string) $input->getOption('base')))->addTranslation(
                (string) $input->getArgument('locale'),
                (string) $input->getArgument('address'),
                (string) $input->getArgument('value'),
                $input->getOption('dictionary') === null ? null : (string) $input->getOption('dictionary')
            );

            $output->writeln("<info>Translation added: {$file}</info>");

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return Command::FAILURE;
        }
    }
}
