<?php

declare(strict_types=1);

namespace Wpkit\Controller;

use InvalidArgumentException;
use Wpkit\Model\ArgumentModel;

class PluginGenerator
{
    /**
     * @param ArgumentModel[] $arguments
     */
    public function __construct(
        private array $arguments,
        private bool $installDependencies = false
    ) {
    }

    public function generate(): void
    {
        $this->normalizeArguments();

        $pluginSlug = ArgumentManager::getValueByName($this->arguments, 'pluginSlug');
        $baseDir = getcwd() . DIRECTORY_SEPARATOR . $pluginSlug;

        if (is_dir($baseDir) && (scandir($baseDir) ?: []) !== ['.', '..']) {
            throw new InvalidArgumentException("Plugin directory already exists and is not empty: {$baseDir}");
        }

        if (!file_exists($baseDir)) {
            mkdir($baseDir, 0777, true);
        }

        chdir($baseDir);

        $this->createDirectoryStructure();
        $this->createComposerJson();

        TemplateEngine::generate('Main.template', $this->arguments, 'src/Main.php');
        TemplateEngine::generate('Boot.template', $this->arguments, 'src/Boot.php');
        TemplateEngine::generate('PluginFile.template', $this->arguments, "{$pluginSlug}.php");
        TemplateEngine::generate('ViewsYaml.template', $this->arguments, 'config/views.yaml');
        TemplateEngine::generate('.buildignore.template', $this->arguments, '.buildignore');
    }

    private function normalizeArguments(): void
    {
        $pluginName = PluginMetadata::normalizePluginName(
            (string) ArgumentManager::getValueByName($this->arguments, 'pluginName')
        );

        if ($pluginName === '') {
            throw new InvalidArgumentException('pluginName is required.');
        }

        $pluginSlug = PluginMetadata::slugify(
            (string) (
                ArgumentManager::getValueByName($this->arguments, 'pluginSlug')
                ?: $pluginName
            )
        );

        $namespace = PluginMetadata::namespaceify(
            (string) (
                ArgumentManager::getValueByName($this->arguments, 'namespace')
                ?: $pluginSlug
            )
        );

        $textDomain = PluginMetadata::slugify(
            (string) (
                ArgumentManager::getValueByName($this->arguments, 'textDomain')
                ?: $pluginSlug
            )
        );

        ArgumentManager::setValueByName($this->arguments, 'pluginName', $pluginName);
        ArgumentManager::setValueByName($this->arguments, 'pluginSlug', $pluginSlug);
        ArgumentManager::setValueByName(
            $this->arguments,
            'vendor',
            PluginMetadata::normalizeVendor(
                (string) ArgumentManager::getValueByName($this->arguments, 'vendor')
            )
        );
        ArgumentManager::setValueByName($this->arguments, 'namespace', $namespace);
        ArgumentManager::setValueByName(
            $this->arguments,
            'description',
            (string) (
                ArgumentManager::getValueByName($this->arguments, 'description')
                ?: "WordPress plugin {$pluginName}"
            )
        );
        ArgumentManager::setValueByName($this->arguments, 'version', (string) (
            ArgumentManager::getValueByName($this->arguments, 'version')
            ?: '1.0.0'
        ));
        ArgumentManager::setValueByName($this->arguments, 'phpVersion', (string) (
            ArgumentManager::getValueByName($this->arguments, 'phpVersion')
            ?: '8.1'
        ));
        ArgumentManager::setValueByName($this->arguments, 'license', (string) (
            ArgumentManager::getValueByName($this->arguments, 'license')
            ?: 'GPL-2.0-or-later'
        ));
        ArgumentManager::setValueByName($this->arguments, 'licenseURI', (string) (
            ArgumentManager::getValueByName($this->arguments, 'licenseURI')
            ?: 'https://www.gnu.org/licenses/gpl-2.0.html'
        ));
        ArgumentManager::setValueByName($this->arguments, 'textDomain', $textDomain);
        ArgumentManager::setValueByName(
            $this->arguments,
            'domainPath',
            PluginMetadata::normalizeDomainPath(
                (string) ArgumentManager::getValueByName($this->arguments, 'domainPath')
            )
        );
    }

    /**
     * Create the composer.json file for the plugin.
     *
     * @return void
     */
    private function createComposerJson(): void
    {
        $pluginName = ArgumentManager::getValueByName($this->arguments, 'pluginName');
        $pluginSlug = ArgumentManager::getValueByName($this->arguments, 'pluginSlug');
        $vendor = ArgumentManager::getValueByName($this->arguments, 'vendor');
        $author = ArgumentManager::getValueByName($this->arguments, 'author');
        $phpVersion = ArgumentManager::getValueByName($this->arguments, 'phpVersion');
        $license = ArgumentManager::getValueByName($this->arguments, 'license');
        $namespace = ArgumentManager::getValueByName($this->arguments, 'namespace');

        $composerConfig = [
            'name' => "{$vendor}/{$pluginSlug}",
            'description' => ArgumentManager::getValueByName($this->arguments, 'description') ?: "WordPress plugin {$pluginName}",
            'type' => 'wordpress-plugin',
            'license' => $license,
            'authors' => [
                ['name' => $author],
            ],
            'require' => [
                'php' => "^{$phpVersion}",
                'arraydev/wptoolkit' => '^1.1@beta'
            ],
            'autoload' => [
                'psr-4' => [
                    "{$namespace}\\" => 'src/'
                ]
            ],
            'minimum-stability' => 'stable',
            'prefer-stable' => true
        ];

        file_put_contents(
            getcwd() . '/composer.json',
            json_encode($composerConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        if ($this->installDependencies) {
            shell_exec('composer install --no-interaction --prefer-dist');
        }
    }

    /**
     * Create the directory structure for the plugin.
     *
     * @return void
     */
    private function createDirectoryStructure(): void
    {
        $this->makeDirectory('src');
        $this->makeDirectory('assets');
        $this->makeDirectory('config');
        $this->makeDirectory('views');
        $this->makeDirectory('src/Admin');
        $this->makeDirectory('src/Hooks');
        $this->makeDirectory('src/Http/Ajax');
        $this->makeDirectory('src/Http/Params');
        $this->makeDirectory('src/Http/Routes');
        $this->makeDirectory('src/PostTypes');
        $this->makeDirectory('src/Shortcodes');
        $this->makeDirectory('src/Service');
        $this->makeDirectory('src/Widgets');
        $this->makeDirectory(ltrim(
            (string) ArgumentManager::getValueByName($this->arguments, 'domainPath'),
            '/\\'
        ));
    }

    /**
     * Create a directory if it does not exist.
     *
     * @param string $path
     * @return void
     */
    private function makeDirectory(string $path): void
    {
        $fullPath = getcwd() . '/' . $path;

        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0777, true);
        }
    }
}
