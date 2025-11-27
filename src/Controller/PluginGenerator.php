<?php

namespace Wpkit\Controller;

use Wpkit\Model\ArgumentModel;

class PluginGenerator
{
    /**
     * @param ArgumentModel[] $arguments
     */
    public function __construct(
        private array $arguments
    ) {
    }

    public function generate(): void
    {
        $pluginName = ArgumentManager::getValueByName($this->arguments, "pluginName");
        $baseDir = getcwd() . '/' . $pluginName;

        if (!file_exists($baseDir)) {
            mkdir($baseDir, 0777, true);
        }

        chdir($baseDir);

        $this->createDirectoryStructure();
        $this->createComposerJson();

        TemplateEngine::generate('Main.template', $this->arguments, 'src/Main.php');
        TemplateEngine::generate('Boot.template', $this->arguments, 'src/Boot.php');
        TemplateEngine::generate('PluginFile.template', $this->arguments, "{$pluginName}.php");
        TemplateEngine::generate('ViewsYaml.template', $this->arguments, 'configs/views.yml');
        TemplateEngine::generate('WelcomeView.template', $this->arguments, 'templates/Welcome.php');
    }

    private function createComposerJson(): void
    {
        $pluginName  = ArgumentManager::getValueByName($this->arguments, "pluginName");
        $vendor      = ArgumentManager::getValueByName($this->arguments, "vendor");
        $author      = ArgumentManager::getValueByName($this->arguments, "author");
        $phpVersion  = ArgumentManager::getValueByName($this->arguments, "phpVersion");
        $license     = ArgumentManager::getValueByName($this->arguments, "license");
        $namespace   = ArgumentManager::getValueByName($this->arguments, "namespace");

        $composerConfig = [
            'name' => "{$vendor}/{$pluginName}",
            'description' => "WordPress plugin {$pluginName}",
            'type' => 'wordpress-plugin',
            'license' => $license,
            'authors' => [
                ['name' => $author],
            ],
            'require' => [
                "php" => "^{$phpVersion}",
                "arraydev/wptoolkit" => "*"
            ],
            'autoload' => [
                'psr-4' => [
                    "{$namespace}\\" => "src/"
                ]
            ],
            'minimum-stability' => 'stable',
            'prefer-stable' => true
        ];

        file_put_contents(
            getcwd() . '/composer.json',
            json_encode($composerConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        shell_exec('composer install --no-interaction --prefer-dist');
    }

    private function createDirectoryStructure(): void
    {
        $this->makeDirectory('src');
        $domainPath = ArgumentManager::getValueByName($this->arguments, "domainPath");
        $this->makeDirectory($domainPath);
        $this->makeDirectory('assets');
        $this->makeDirectory('configs');
        $this->makeDirectory('templates');
    }

    private function makeDirectory(string $path): void
    {
        $fullPath = getcwd() . '/' . $path;

        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0777, true);
        }
    }
}
