<?php

namespace Wpkit\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class ToolkitIsolateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('toolkit:isolate')
            ->setDescription('Изолирует WPToolkit под указанный namespace плагина.')
            ->addArgument('source', InputArgument::REQUIRED, 'Путь до директории WPToolkit (например, src/WpToolKit)')
            ->addArgument('namespace', InputArgument::REQUIRED, 'Namespace плагина (например, MyPlugin)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = rtrim($input->getArgument('source'), '/');
        $namespace = trim($input->getArgument('namespace'));

        if (!is_dir($source)) {
            $io->error("Директория '$source' не найдена.");
            return Command::FAILURE;
        }

        $io->section("Замена namespace на {$namespace}\\WpToolKit");

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source));

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            $contents = preg_replace(
                '/namespace\s+WpToolKit(\\\[^;]*)?;/',
                "namespace {$namespace}\\\\WpToolKit\$1;",
                $contents
            );

            $contents = preg_replace(
                '/use\s+WpToolKit(\\\[^;]*)?;/',
                "use {$namespace}\\\\WpToolKit\$1;",
                $contents
            );

            file_put_contents($file->getPathname(), $contents);
        }

        $io->success("Namespace успешно обновлён.");
        return Command::SUCCESS;
    }
}
