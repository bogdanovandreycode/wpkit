<?php

declare(strict_types=1);

namespace Wpkit\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wpkit\Controller\LocaleTranslationController;

final class LocaleShowCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('locale:show')
            ->setAliases(['translation:show'])
            ->setDescription('Show translation values for all languages by address.')
            ->addArgument('address', InputArgument::REQUIRED, 'Translation address, for example payment.maib_payment.')
            ->addOption('base', 'b', InputOption::VALUE_REQUIRED, 'Translation base directory.', 'languages')
            ->addOption('dictionary', 'd', InputOption::VALUE_REQUIRED, 'Dictionary path when address is only a key.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $rows = (new LocaleTranslationController((string) $input->getOption('base')))->listValues(
                (string) $input->getArgument('address'),
                $input->getOption('dictionary') === null ? null : (string) $input->getOption('dictionary')
            );

            if ((bool) $input->getOption('json')) {
                $output->writeln(json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                return Command::SUCCESS;
            }

            $table = new Table($output);
            $table->setHeaders(['Locale', 'Found', 'Value', 'File']);

            foreach ($rows as $row) {
                $table->addRow([
                    $row['locale'],
                    $row['found'] ? 'yes' : 'no',
                    $this->formatValue($row['value']),
                    $row['file'] ?? '',
                ]);
            }

            $table->render();

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return Command::FAILURE;
        }
    }

    private function formatValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
        }

        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
