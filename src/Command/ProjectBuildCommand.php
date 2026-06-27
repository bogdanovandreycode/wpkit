<?php

declare(strict_types=1);

namespace Wpkit\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectBuildCommand extends Command
{
    private const VENDOR_SCOPE_NONE = 'none';
    private const VENDOR_SCOPE_PLUGIN = 'plugin';
    private const SCOPED_VENDOR_DIR = 'vendor_scoped';
    private const TOOLKIT_NAMESPACE = 'WpToolKit';
    private const TOOLKIT_SCOPED_SUFFIX = 'Vendor\\WpToolKit';

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
            )
            ->addOption(
                'vendor-scope',
                null,
                InputOption::VALUE_REQUIRED,
                'Vendor isolation mode: none or plugin',
                self::VENDOR_SCOPE_NONE
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
        $vendorScope = $this->normalizeVendorScope((string) $input->getOption('vendor-scope'));

        if ($vendorScope === null) {
            $output->writeln('<error>Invalid --vendor-scope value. Allowed values: none, plugin.</error>');

            return Command::FAILURE;
        }

        $ignored = $this->getIgnoredPaths($buildignore);

        $output->writeln("📦 <info>Build started...</info>");

        $this->deleteFolder($buildDir);

        $this->copyFiles($rootDir, $projectBuildDir, $ignored, $output);

        if ($vendorScope === self::VENDOR_SCOPE_PLUGIN && !$this->scopeVendor($projectBuildDir, $output)) {
            $output->writeln("<error>Build aborted at vendor scoping stage.</error>");

            return Command::FAILURE;
        }

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

    private function normalizeVendorScope(string $value): ?string
    {
        $value = strtolower(trim($value));

        return match ($value) {
            '', self::VENDOR_SCOPE_NONE, 'default', 'standard' => self::VENDOR_SCOPE_NONE,
            self::VENDOR_SCOPE_PLUGIN, 'scoped', 'namespace', 'namespaced' => self::VENDOR_SCOPE_PLUGIN,
            default => null,
        };
    }

    private function scopeVendor(string $projectBuildDir, OutputInterface $output): bool
    {
        $vendorDir = $projectBuildDir . DIRECTORY_SEPARATOR . 'vendor';
        $scopedVendorDir = $projectBuildDir . DIRECTORY_SEPARATOR . self::SCOPED_VENDOR_DIR;

        if (!is_dir($vendorDir)) {
            $output->writeln("<error>Vendor directory not found: {$vendorDir}</error>");

            return false;
        }

        $pluginNamespace = $this->readPluginNamespace($projectBuildDir);

        if ($pluginNamespace === null) {
            $output->writeln('<error>Unable to detect plugin namespace from composer.json autoload.psr-4.</error>');

            return false;
        }

        if (is_dir($scopedVendorDir)) {
            $this->deleteFolder($scopedVendorDir);
        }

        if (!@rename($vendorDir, $scopedVendorDir)) {
            $output->writeln("<error>Failed to move vendor to " . self::SCOPED_VENDOR_DIR . '.</error>');

            return false;
        }

        $scopedNamespace = $pluginNamespace . '\\' . self::TOOLKIT_SCOPED_SUFFIX;
        $changedFiles = $this->rewriteNamespaceFiles(
            $projectBuildDir,
            self::TOOLKIT_NAMESPACE,
            $scopedNamespace
        );
        $autoloadFiles = $this->rewriteAutoloadPath($projectBuildDir);

        $output->writeln(
            '🔒 Vendor scoped: <comment>' .
            self::TOOLKIT_NAMESPACE .
            '</comment>\\ -> <comment>' .
            $scopedNamespace .
            '</comment>\\'
        );
        $output->writeln('📦 Scoped vendor dir: <comment>' . self::SCOPED_VENDOR_DIR . '</comment>');
        $output->writeln("📝 Namespace files rewritten: <comment>{$changedFiles}</comment>");
        $output->writeln("🔗 Autoload references updated: <comment>{$autoloadFiles}</comment>");

        return true;
    }

    private function readPluginNamespace(string $projectBuildDir): ?string
    {
        $composerJson = $projectBuildDir . DIRECTORY_SEPARATOR . 'composer.json';

        if (!is_file($composerJson)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($composerJson), true);

        if (!is_array($data)) {
            return null;
        }

        $psr4 = $data['autoload']['psr-4'] ?? null;

        if (!is_array($psr4)) {
            return null;
        }

        foreach ($psr4 as $namespace => $path) {
            if (!is_string($namespace)) {
                continue;
            }

            $namespace = trim($namespace, '\\');

            if ($namespace === '' || $namespace === self::TOOLKIT_NAMESPACE) {
                continue;
            }

            return $namespace;
        }

        return null;
    }

    private function rewriteNamespaceFiles(string $directory, string $fromNamespace, string $toNamespace): int
    {
        $filesChanged = 0;
        $extensions = ['php', 'json'];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || !in_array(strtolower($file->getExtension()), $extensions, true)) {
                continue;
            }

            $filePath = $file->getPathname();
            $contents = file_get_contents($filePath);

            if ($contents === false || !str_contains($contents, $fromNamespace)) {
                continue;
            }

            $rewritten = str_replace($fromNamespace, $toNamespace, $contents);

            if ($rewritten !== $contents) {
                file_put_contents($filePath, $rewritten);
                $filesChanged++;
            }
        }

        return $filesChanged;
    }

    private function rewriteAutoloadPath(string $directory): int
    {
        $filesChanged = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $filePath = $file->getPathname();
            $contents = file_get_contents($filePath);

            if ($contents === false || !str_contains($contents, '/vendor/autoload.php')) {
                continue;
            }

            $rewritten = str_replace(
                '/vendor/autoload.php',
                '/' . self::SCOPED_VENDOR_DIR . '/autoload.php',
                $contents
            );

            if ($rewritten !== $contents) {
                file_put_contents($filePath, $rewritten);
                $filesChanged++;
            }
        }

        return $filesChanged;
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
        if (basename($source) === 'build') {
            return;
        }

        $normalizedSource = str_replace('\\', '/', realpath($source) ?: $source);
        $normalizedBuild  = str_replace('\\', '/', realpath(getcwd() . '/build') ?: (getcwd() . '/build'));

        if ($normalizedSource === $normalizedBuild) {
            return;
        }

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
