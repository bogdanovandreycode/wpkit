<?php

declare(strict_types=1);

namespace Wpkit\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectBuildCommand extends Command
{
    /**
     * Configure the command options and arguments.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('project:build')
            ->setDescription('Creates a production build of the project using .buildignore.')
            ->addOption(
                'zip',
                'z',
                InputOption::VALUE_NONE,
                'Create ZIP and remove project folder inside build/'
            );
    }

    /**
     * Execute the project build command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rootDir = getcwd();
        $buildignore = $rootDir . DIRECTORY_SEPARATOR . '.buildignore';

        if (!file_exists($buildignore)) {
            $output->writeln("<error>.buildignore file not found in {$rootDir}. Build cancelled.</error>");

            return Command::FAILURE;
        }

        $buildDir = $rootDir . DIRECTORY_SEPARATOR . 'build';
        $projectName = basename($rootDir);
        $projectBuildDir = $buildDir . DIRECTORY_SEPARATOR . $projectName;
        $zipFile = $buildDir . DIRECTORY_SEPARATOR . $projectName . '.zip';
        $zipFlag = (bool)$input->getOption('zip');
        $ignored = $this->getIgnoredPaths($buildignore);

        $output->writeln("📦 <info>Build started...</info>");

        $this->deleteFolder($buildDir);

        $this->copyFiles($rootDir, $projectBuildDir, $ignored, $output);

        $output->writeln("🗜️  <info>Creating ZIP archive...</info>");
        $zipOk = $this->createZipArchive($projectBuildDir, $zipFile, $projectName, $output);

        if (!$zipOk) {
            $output->writeln("<error>Build aborted at ZIP stage.</error>");

            return Command::FAILURE;
        }

        if ($zipFlag) {
            $output->writeln("🧹 Removing {$projectBuildDir}");
            $this->deleteFolder($projectBuildDir);
            $output->writeln("🧾 Project folder removed (--zip flag).");
        } else {
            $output->writeln("📁 Project folder kept: {$projectBuildDir}");
        }

        $output->writeln("");
        $output->writeln("✅ <info>Build completed</info>");
        $output->writeln("📂 Build dir: <comment>{$buildDir}</comment>");
        $output->writeln("📄 Zip file:  <comment>{$zipFile}</comment>");

        return Command::SUCCESS;
    }

    /**
     * Parse the .buildignore file and return an array of ignored paths.
     *
     * @param string $filePath
     * @return array
     */
    private function getIgnoredPaths(string $filePath): array
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (!$lines) {
            return [];
        }

        return array_map('trim', $lines);
    }

    /**
     * Recursively delete a folder and all its contents.
     *
     * @param string $folder
     * @return void
     */
    private function deleteFolder(string $folder): void
    {
        if (!is_dir($folder)) {
            return;
        }

        $items = array_diff(scandir($folder) ?: [], ['.', '..']);

        foreach ($items as $item) {
            $path = $folder . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path) && !is_link($path)) {
                $this->deleteFolder($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($folder);
    }

    /**
     * Copy files from source to destination while respecting ignore patterns.
     *
     * @param string $source
     * @param string $destination
     * @param array $ignored
     * @param OutputInterface $output
     * @return void
     */
    private function copyFiles(
        string $source,
        string $destination,
        array $ignored,
        OutputInterface $output
    ): void {

        if (!is_dir($destination)) {
            @mkdir($destination, 0755, true);
        }

        $files = scandir($source) ?: [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcPath = $source . DIRECTORY_SEPARATOR . $file;
            $destPath = $destination . DIRECTORY_SEPARATOR . $file;

            foreach ($ignored as $ignoredPath) {
                $ignoredFullPath = realpath(getcwd() . DIRECTORY_SEPARATOR . $ignoredPath);
                $realSrcPath = realpath($srcPath);

                if ($ignoredFullPath && $realSrcPath && str_starts_with($realSrcPath, $ignoredFullPath)) {
                    continue 2;
                }
            }

            if (is_dir($srcPath) && !is_link($srcPath)) {
                $this->copyFiles($srcPath, $destPath, $ignored, $output);
            } else {
                if (!@copy($srcPath, $destPath)) {
                    $output->writeln("⚠️  Failed to copy: {$srcPath}");
                }
            }
        }
    }

    /**
     * Create a ZIP archive from the source directory.
     *
     * @param string $source
     * @param string $destination
     * @param string $projectName
     * @param OutputInterface $output
     * @return bool
     */
    private function createZipArchive(
        string $source,
        string $destination,
        string $projectName,
        OutputInterface $output
    ): bool {
        if (!class_exists('ZipArchive')) {
            $output->writeln("<error>PHP ZipArchive extension is not available.</error>");

            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($destination, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $output->writeln("<error>Failed to create ZIP: {$destination}</error>");

            return false;
        }

        $sourceReal = realpath($source);
        if (!$sourceReal) {
            $output->writeln("<error>Source not found: {$source}</error>");

            return false;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceReal, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($sourceReal) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);

            if (!$zip->addFile($filePath, $projectName . '/' . $relativePath)) {
                $output->writeln("⚠️  Failed to add: {$filePath}");
            }
        }

        $zip->close();
        $output->writeln("🗜️  ZIP archive created: {$destination}");

        return true;
    }
}
