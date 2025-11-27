<?php

namespace Wpkit\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class NamespaceUpdateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('toolkit:namespace-update')
            ->setDescription('Обновляет namespace WpToolKit на указанную версию во всех PHP-файлах.')
            ->addArgument('path', InputArgument::REQUIRED, 'Путь до директории плагина (например: src/ или ./)')
            ->addArgument('version', InputArgument::REQUIRED, 'Версия WPToolkit (например: v2)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Не записывать изменения, только показать')
            ->addOption('backup', null, InputOption::VALUE_NONE, 'Создавать резервную копию .bak перед изменением')
            ->addOption('extensions', null, InputOption::VALUE_OPTIONAL, 'Список расширений через запятую', 'php');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $path = rtrim($input->getArgument('path'), '/');
        $version = trim($input->getArgument('version'));
        $dryRun = $input->getOption('dry-run');
        $createBackup = $input->getOption('backup');
        $extensions = explode(',', strtolower($input->getOption('extensions')));

        if (!is_dir($path)) {
            $io->error("Директория '$path' не найдена.");

            return Command::FAILURE;
        }

        $io->section("Обновление namespace WpToolKit → WpToolKit\\{$version}");
        $io->text("Путь: $path");
        $io->text("Dry-run: " . ($dryRun ? '✅' : '❌'));
        $io->text("Backup: " . ($createBackup ? '✅' : '❌'));
        $io->text("Фильтр по расширениям: " . implode(', ', $extensions));

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        $filesChanged = 0;

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, $extensions)) {
                continue;
            }

            $filePath = $file->getPathname();
            $contents = file_get_contents($filePath);
            $original = $contents;

            $contents = preg_replace(
                '/use\s+WpToolKit\\\\(?!v\d+)([^\s;]+);/',
                "use WpToolKit\\\\{$version}\\\\\$1;",
                $contents
            );

            $contents = preg_replace(
                '/new\s+WpToolKit\\\\(?!v\d+)([^\s\(]+)/',
                "new WpToolKit\\\\{$version}\\\\\$1",
                $contents
            );

            $contents = preg_replace(
                '/\\\\WpToolKit\\\\(?!v\d+)([^\s;]+)/',
                "\\\\WpToolKit\\\\{$version}\\\\\$1",
                $contents
            );

            if ($contents !== $original) {
                $filesChanged++;

                if ($dryRun) {
                    $io->writeln("⚠ Изменится: {$filePath}");
                } else {
                    if ($createBackup) {
                        copy($filePath, $filePath . '.bak');
                    }

                    file_put_contents($filePath, $contents);
                }
            }
        }

        $io->success("Файлов затронуто: {$filesChanged}");

        if ($dryRun) {
            $io->note('Это был dry-run, никаких изменений не внесено.');
        }

        return Command::SUCCESS;
    }
}
