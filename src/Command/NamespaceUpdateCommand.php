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
    /**
     * Configure the command options and arguments.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('toolkit:namespace-update')
            ->setDescription('Updates WpToolKit namespace to the specified version in all PHP files.')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to plugin directory (e.g., src/ or ./)')
            ->addArgument('version', InputArgument::REQUIRED, 'WPToolkit version (e.g., v2)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not write changes, only show them')
            ->addOption('backup', null, InputOption::VALUE_NONE, 'Create .bak backup before modification')
            ->addOption('extensions', null, InputOption::VALUE_OPTIONAL, 'Comma-separated list of extensions', 'php');
    }

    /**
     * Execute the namespace update command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $path = rtrim($input->getArgument('path'), '/');
        $version = trim($input->getArgument('version'));
        $dryRun = $input->getOption('dry-run');
        $createBackup = $input->getOption('backup');
        $extensions = explode(',', strtolower($input->getOption('extensions')));

        if (!is_dir($path)) {
            $io->error("Directory '$path' not found.");

            return Command::FAILURE;
        }

        $io->section("Updating namespace WpToolKit → WpToolKit\\{$version}");
        $io->text("Path: $path");
        $io->text("Dry-run: " . ($dryRun ? '✅' : '❌'));
        $io->text("Backup: " . ($createBackup ? '✅' : '❌'));
        $io->text("Extension filter: " . implode(', ', $extensions));

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
                    $io->writeln("⚠ Will be changed: {$filePath}");
                } else {
                    if ($createBackup) {
                        copy($filePath, $filePath . '.bak');
                    }

                    file_put_contents($filePath, $contents);
                }
            }
        }

        $io->success("Files affected: {$filesChanged}");

        if ($dryRun) {
            $io->note('This was a dry-run, no changes were made.');
        }

        return Command::SUCCESS;
    }
}
