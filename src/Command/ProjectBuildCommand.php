<?php

declare(strict_types=1);

namespace Wpkit\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectBuildCommand extends Command
{
    private const BUILD_CONFIG_FILE = '.build.json';
    private const VENDOR_MODE_NONE = 'none';
    private const VENDOR_MODE_STANDARD = 'standard';
    private const VENDOR_MODE_SCOPED = 'scoped';
    private const DEFAULT_SCOPED_VENDOR_DIR = 'vendor_scoped';
    private const TOOLKIT_NAMESPACE = 'WpToolKit';
    private const TOOLKIT_SCOPED_SUFFIX = 'Vendor\\WpToolKit';

    /**
     * @return array<string, mixed>
     */
    private function defaultConfig(): array
    {
        return [
            'composer' => [
                'installDev' => true,
                'installNoDev' => true,
                'keepComposerFiles' => false,
            ],
            'tests' => [
                'enabled' => true,
                'command' => 'composer test',
            ],
            'vendor' => [
                'mode' => self::VENDOR_MODE_SCOPED,
                'scopedDir' => self::DEFAULT_SCOPED_VENDOR_DIR,
                'keepOriginal' => false,
            ],
            'zip' => [
                'enabled' => true,
            ],
            'cleanup' => [
                'removeBuildDir' => false,
            ],
        ];
    }

    protected function configure(): void
    {
        $this
            ->setName('project:build')
            ->setDescription('Creates a production build of the project using .buildignore and .build.json.')
            ->addOption(
                'zip',
                'z',
                InputOption::VALUE_NONE,
                'Create ZIP and remove project folder inside build/. Overrides .build.json cleanup.removeBuildDir.'
            )
            ->addOption(
                'vendor-scope',
                null,
                InputOption::VALUE_REQUIRED,
                'Vendor mode override: none, standard or scoped'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rootDir = getcwd();
        $buildignore = $rootDir . DIRECTORY_SEPARATOR . '.buildignore';

        if (!file_exists($buildignore)) {
            $output->writeln("<error>.buildignore file not found in {$rootDir}. Build cancelled.</error>");

            return Command::FAILURE;
        }

        $config = $this->loadBuildConfig($rootDir, $output);
        $config = $this->applyCliOverrides($config, $input);

        if (!$this->validateBuildConfig($config, $output)) {
            return Command::FAILURE;
        }

        $buildDir = $rootDir . DIRECTORY_SEPARATOR . 'build';
        $projectName = basename($rootDir);
        $projectBuildDir = $buildDir . DIRECTORY_SEPARATOR . $projectName;
        $zipFile = $buildDir . DIRECTORY_SEPARATOR . $projectName . '.zip';
        $ignored = $this->getIgnoredPaths($buildignore);

        $output->writeln('<info>Build started...</info>');

        $this->deleteFolder($buildDir);
        $this->copyFiles($rootDir, $projectBuildDir, $ignored, $output);
        $this->copyComposerFiles($rootDir, $projectBuildDir, $output);

        if ($this->configBool($config, 'composer.installDev')) {
            if (!$this->runComposerInstall($projectBuildDir, false, $output)) {
                $output->writeln('<error>Build aborted: composer install with dev dependencies failed.</error>');

                return Command::FAILURE;
            }
        }

        if ($this->configBool($config, 'tests.enabled')) {
            $testCommand = $this->configString($config, 'tests.command', 'composer test');

            if (!$this->runShellCommand($testCommand, $projectBuildDir, $output)) {
                $output->writeln('<error>Build aborted: unit tests failed.</error>');

                return Command::FAILURE;
            }
        }

        if ($this->configBool($config, 'composer.installNoDev')) {
            $this->deleteFolder($projectBuildDir . DIRECTORY_SEPARATOR . 'vendor');

            if (!$this->runComposerInstall($projectBuildDir, true, $output)) {
                $output->writeln('<error>Build aborted: composer install --no-dev failed.</error>');

                return Command::FAILURE;
            }
        }

        if (!$this->processVendor($projectBuildDir, $config, $output)) {
            $output->writeln('<error>Build aborted at vendor stage.</error>');

            return Command::FAILURE;
        }

        if (!$this->configBool($config, 'composer.keepComposerFiles')) {
            $this->removeComposerFiles($projectBuildDir);
        }

        $zipEnabled = $this->configBool($config, 'zip.enabled');

        if ($zipEnabled) {
            $output->writeln('<info>Creating ZIP archive...</info>');

            if (!$this->createZipArchive($projectBuildDir, $zipFile, $projectName, $output)) {
                $output->writeln('<error>Build aborted at ZIP stage.</error>');

                return Command::FAILURE;
            }
        }

        if ($this->configBool($config, 'cleanup.removeBuildDir')) {
            $output->writeln("Removing {$projectBuildDir}");
            $this->deleteFolder($projectBuildDir);
        } else {
            $output->writeln("Project folder kept: {$projectBuildDir}");
        }

        $output->writeln('');
        $output->writeln('<info>Build completed</info>');
        $output->writeln("Build dir: <comment>{$buildDir}</comment>");

        if ($zipEnabled) {
            $output->writeln("Zip file:  <comment>{$zipFile}</comment>");
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadBuildConfig(string $rootDir, OutputInterface $output): array
    {
        $configFile = $rootDir . DIRECTORY_SEPARATOR . self::BUILD_CONFIG_FILE;
        $defaultConfig = $this->defaultConfig();

        if (!is_file($configFile)) {
            $output->writeln('<comment>.build.json not found, using default build config.</comment>');

            return $defaultConfig;
        }

        $data = json_decode((string) file_get_contents($configFile), true);

        if (!is_array($data)) {
            $output->writeln('<comment>.build.json is invalid, using default build config.</comment>');

            return $defaultConfig;
        }

        return array_replace_recursive($defaultConfig, $data);
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function applyCliOverrides(array $config, InputInterface $input): array
    {
        $vendorMode = $input->getOption('vendor-scope');

        if (is_string($vendorMode) && trim($vendorMode) !== '') {
            $config['vendor']['mode'] = $this->normalizeVendorMode($vendorMode);
        }

        if ((bool) $input->getOption('zip')) {
            $config['zip']['enabled'] = true;
            $config['cleanup']['removeBuildDir'] = true;
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function validateBuildConfig(array $config, OutputInterface $output): bool
    {
        $vendorMode = $this->configString($config, 'vendor.mode', self::VENDOR_MODE_SCOPED);

        if (!in_array($vendorMode, [
            self::VENDOR_MODE_NONE,
            self::VENDOR_MODE_STANDARD,
            self::VENDOR_MODE_SCOPED,
        ], true)) {
            $output->writeln('<error>Invalid vendor.mode. Allowed values: none, standard, scoped.</error>');

            return false;
        }

        $scopedDir = $this->configString($config, 'vendor.scopedDir', self::DEFAULT_SCOPED_VENDOR_DIR);

        if ($scopedDir === '' || str_contains($scopedDir, '/') || str_contains($scopedDir, '\\')) {
            $output->writeln('<error>Invalid vendor.scopedDir. Use a single directory name.</error>');

            return false;
        }

        if ($this->configBool($config, 'cleanup.removeBuildDir') && !$this->configBool($config, 'zip.enabled')) {
            $output->writeln('<error>cleanup.removeBuildDir requires zip.enabled=true.</error>');

            return false;
        }

        return true;
    }

    private function normalizeVendorMode(string $value): string
    {
        $value = strtolower(trim($value));

        return match ($value) {
            '', self::VENDOR_MODE_SCOPED, 'plugin', 'namespace', 'namespaced' => self::VENDOR_MODE_SCOPED,
            self::VENDOR_MODE_STANDARD, 'default', 'vendor' => self::VENDOR_MODE_STANDARD,
            self::VENDOR_MODE_NONE, 'disabled', 'delete' => self::VENDOR_MODE_NONE,
            default => $value,
        };
    }

    /**
     * @param array<string, mixed> $config
     */
    private function processVendor(string $projectBuildDir, array $config, OutputInterface $output): bool
    {
        $mode = $this->normalizeVendorMode($this->configString($config, 'vendor.mode', self::VENDOR_MODE_SCOPED));
        $vendorDir = $projectBuildDir . DIRECTORY_SEPARATOR . 'vendor';

        if ($mode === self::VENDOR_MODE_NONE) {
            $this->deleteFolder($vendorDir);
            $output->writeln('Vendor removed by config.');

            return true;
        }

        if (!is_dir($vendorDir)) {
            $output->writeln("<error>Vendor directory not found: {$vendorDir}</error>");

            return false;
        }

        if ($mode === self::VENDOR_MODE_STANDARD) {
            $output->writeln('Vendor kept as standard vendor/.');

            return true;
        }

        return $this->scopeVendor($projectBuildDir, $config, $output);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function scopeVendor(string $projectBuildDir, array $config, OutputInterface $output): bool
    {
        $vendorDir = $projectBuildDir . DIRECTORY_SEPARATOR . 'vendor';
        $scopedDirName = $this->configString($config, 'vendor.scopedDir', self::DEFAULT_SCOPED_VENDOR_DIR);
        $scopedVendorDir = $projectBuildDir . DIRECTORY_SEPARATOR . $scopedDirName;
        $keepOriginal = $this->configBool($config, 'vendor.keepOriginal');

        $pluginNamespace = $this->readPluginNamespace($projectBuildDir);

        if ($pluginNamespace === null) {
            $output->writeln('<error>Unable to detect plugin namespace from composer.json autoload.psr-4.</error>');

            return false;
        }

        if (is_dir($scopedVendorDir)) {
            $this->deleteFolder($scopedVendorDir);
        }

        if ($keepOriginal) {
            $this->copyDirectory($vendorDir, $scopedVendorDir, $output);
        } elseif (!@rename($vendorDir, $scopedVendorDir)) {
            $output->writeln("<error>Failed to move vendor to {$scopedDirName}.</error>");

            return false;
        }

        $scopedNamespace = $pluginNamespace . '\\' . self::TOOLKIT_SCOPED_SUFFIX;
        $excludedDirs = $keepOriginal ? ['vendor'] : [];
        $changedFiles = $this->rewriteNamespaceFiles(
            $projectBuildDir,
            self::TOOLKIT_NAMESPACE,
            $scopedNamespace,
            $excludedDirs
        );
        $autoloadFiles = $this->rewriteAutoloadPath($projectBuildDir, $scopedDirName, $excludedDirs);

        $output->writeln(
            'Vendor scoped: <comment>' .
            self::TOOLKIT_NAMESPACE .
            '</comment>\\ -> <comment>' .
            $scopedNamespace .
            '</comment>\\'
        );
        $output->writeln("Scoped vendor dir: <comment>{$scopedDirName}</comment>");
        $output->writeln("Namespace files rewritten: <comment>{$changedFiles}</comment>");
        $output->writeln("Autoload references updated: <comment>{$autoloadFiles}</comment>");

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

    /**
     * @param string[] $excludedDirs
     */
    private function rewriteNamespaceFiles(
        string $directory,
        string $fromNamespace,
        string $toNamespace,
        array $excludedDirs = []
    ): int {
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

            if ($this->isPathInsideExcludedDir($filePath, $directory, $excludedDirs)) {
                continue;
            }

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

    /**
     * @param string[] $excludedDirs
     */
    private function rewriteAutoloadPath(string $directory, string $scopedDirName, array $excludedDirs = []): int
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

            if ($this->isPathInsideExcludedDir($filePath, $directory, $excludedDirs)) {
                continue;
            }

            $contents = file_get_contents($filePath);

            if ($contents === false || !str_contains($contents, '/vendor/autoload.php')) {
                continue;
            }

            $rewritten = str_replace(
                '/vendor/autoload.php',
                '/' . $scopedDirName . '/autoload.php',
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
     * @return string[]
     */
    private function getIgnoredPaths(string $filePath): array
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (!$lines) {
            return [];
        }

        $lines = array_map('trim', $lines);

        return array_values(array_filter(
            $lines,
            static fn (string $line): bool => $line !== '' && !str_starts_with($line, '#')
        ));
    }

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
     * @param string[] $ignored
     */
    private function copyFiles(
        string $source,
        string $destination,
        array $ignored,
        OutputInterface $output
    ): void {
        $rootDir = getcwd();

        if ($this->shouldNeverCopy($source, $rootDir)) {
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

            if ($this->shouldNeverCopy($srcPath, $rootDir)) {
                continue;
            }

            if ($this->isIgnored($srcPath, $ignored, $rootDir)) {
                continue;
            }

            if (is_dir($srcPath) && !is_link($srcPath)) {
                $this->copyFiles($srcPath, $destPath, $ignored, $output);
            } elseif (!@copy($srcPath, $destPath)) {
                $output->writeln("Failed to copy: {$srcPath}");
            }
        }
    }

    private function shouldNeverCopy(string $path, string $rootDir): bool
    {
        $relativePath = $this->relativePath($path, $rootDir);
        $firstSegment = explode('/', $relativePath)[0] ?? '';

        return in_array($firstSegment, ['build', 'vendor'], true);
    }

    /**
     * @param string[] $ignored
     */
    private function isIgnored(string $path, array $ignored, string $rootDir): bool
    {
        $realPath = realpath($path);

        if (!$realPath) {
            return false;
        }

        foreach ($ignored as $ignoredPath) {
            $ignoredFullPath = realpath($rootDir . DIRECTORY_SEPARATOR . $ignoredPath);

            if ($ignoredFullPath && str_starts_with($realPath, $ignoredFullPath)) {
                return true;
            }
        }

        return false;
    }

    private function copyComposerFiles(string $rootDir, string $projectBuildDir, OutputInterface $output): void
    {
        foreach (['composer.json', 'composer.lock'] as $file) {
            $source = $rootDir . DIRECTORY_SEPARATOR . $file;

            if (!is_file($source)) {
                continue;
            }

            $destination = $projectBuildDir . DIRECTORY_SEPARATOR . $file;

            if (!@copy($source, $destination)) {
                $output->writeln("Failed to copy required composer file: {$source}");
            }
        }
    }

    private function removeComposerFiles(string $projectBuildDir): void
    {
        foreach (['composer.json', 'composer.lock'] as $file) {
            $path = $projectBuildDir . DIRECTORY_SEPARATOR . $file;

            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function copyDirectory(string $source, string $destination, OutputInterface $output): void
    {
        if (!is_dir($destination)) {
            @mkdir($destination, 0755, true);
        }

        foreach (scandir($source) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $srcPath = $source . DIRECTORY_SEPARATOR . $item;
            $destPath = $destination . DIRECTORY_SEPARATOR . $item;

            if (is_dir($srcPath) && !is_link($srcPath)) {
                $this->copyDirectory($srcPath, $destPath, $output);
            } elseif (!@copy($srcPath, $destPath)) {
                $output->writeln("Failed to copy: {$srcPath}");
            }
        }
    }

    private function runComposerInstall(string $workingDir, bool $noDev, OutputInterface $output): bool
    {
        $command = 'composer install --no-interaction --prefer-dist';

        if ($noDev) {
            $command .= ' --no-dev --optimize-autoloader';
        }

        return $this->runShellCommand($command, $workingDir, $output);
    }

    private function runShellCommand(string $command, string $workingDir, OutputInterface $output): bool
    {
        $output->writeln("Running: <comment>{$command}</comment>");

        $previousDir = getcwd();
        chdir($workingDir);

        $lines = [];
        $exitCode = 0;
        exec($command . ' 2>&1', $lines, $exitCode);

        chdir($previousDir);

        foreach ($lines as $line) {
            $output->writeln($line);
        }

        return $exitCode === 0;
    }

    private function createZipArchive(
        string $source,
        string $destination,
        string $projectName,
        OutputInterface $output
    ): bool {
        if (!class_exists('ZipArchive')) {
            $output->writeln('<error>PHP ZipArchive extension is not available.</error>');

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
                $output->writeln("Failed to add: {$filePath}");
            }
        }

        $zip->close();
        $output->writeln("ZIP archive created: {$destination}");

        return true;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function configBool(array $config, string $path): bool
    {
        $value = $this->configValue($config, $path);

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function configString(array $config, string $path, string $default): string
    {
        $value = $this->configValue($config, $path);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function configValue(array $config, string $path): mixed
    {
        $value = $config;

        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * @param string[] $excludedDirs
     */
    private function isPathInsideExcludedDir(string $path, string $rootDir, array $excludedDirs): bool
    {
        $relativePath = $this->relativePath($path, $rootDir);
        $firstSegment = explode('/', $relativePath)[0] ?? '';

        return in_array($firstSegment, $excludedDirs, true);
    }

    private function relativePath(string $path, string $rootDir): string
    {
        $normalizedPath = str_replace('\\', '/', realpath($path) ?: $path);
        $normalizedRoot = rtrim(str_replace('\\', '/', realpath($rootDir) ?: $rootDir), '/');

        if ($normalizedPath === $normalizedRoot) {
            return '';
        }

        if (str_starts_with($normalizedPath, $normalizedRoot . '/')) {
            return ltrim(substr($normalizedPath, strlen($normalizedRoot)), '/');
        }

        return basename($path);
    }
}
