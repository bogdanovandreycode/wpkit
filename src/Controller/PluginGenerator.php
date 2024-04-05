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
        $this->createComposerProject();
        $this->createDirectoryStructure();
        $this->changeComposerJson();
        TemplateEngine::generate('Main.template', $this->arguments, 'src/Main.php');
        TemplateEngine::generate('Boot.template', $this->arguments, 'src/Boot.php');
        $pluginName = ArgumentManager::getValueByName($this->arguments, "pluginName");
        TemplateEngine::generate('PluginFile.template', $this->arguments, "{$pluginName}.php");
    }

    private function createComposerProject(): void
    {
        $composerFile = getcwd() . '/composer.json';
        $pluginName = ArgumentManager::getValueByName($this->arguments, "pluginName");
        $vendor = ArgumentManager::getValueByName($this->arguments, "vendor");
        $author = ArgumentManager::getValueByName($this->arguments, "author");
        $phpVersion = ArgumentManager::getValueByName($this->arguments, "phpVersion");
        $license = ArgumentManager::getValueByName($this->arguments, "license");

        if (!file_exists($composerFile)) {
            shell_exec("composer init --name {$vendor}/{$pluginName} --author \"{$author}\" --type wordpress-plugin --require php:^{$phpVersion} --stability dev --license {$license}");
        }
    }

    private function changeComposerJson(): void
    {
        $composerJsonPath = getcwd() . '/composer.json';

        if (empty($composerJsonPath)) {
            return;
        }

        $composerConfig = json_decode(file_get_contents($composerJsonPath), true);
        $namespace = ArgumentManager::getValueByName($this->arguments, "namespace");
        $composerConfig['autoload'] = [
            'psr-4' => ["{$namespace}\\" => "src/"]
        ];

        file_put_contents($composerJsonPath, json_encode($composerConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        shell_exec("composer dump-autoload");
    }

    private function createDirectoryStructure(): void
    {
        $this->makeDirectory('src');
        $domainPath = ArgumentManager::getValueByName($this->arguments, "domainPath");
        $this->makeDirectory($domainPath);
        $this->makeDirectory('assets');
    }

    private function makeDirectory($path): void
    {
        $fullPath = getcwd() . '/' . $path;

        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0777, true);
        }
    }
}
