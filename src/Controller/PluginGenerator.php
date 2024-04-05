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
        $this->createComposerProject();
        $this->changeComposerJson();
        $this->createDirectoryStructure();
        TemplateEngine::generate('Main.template', $this->arguments, 'src/Main.php');
        TemplateEngine::generate('Boot.template', $this->arguments, 'src/Boot.php');
        $pluginName = ArgumentManager::getValueByName($this->arguments, "pluginName");
        TemplateEngine::generate('PluginFile.template', $this->arguments, "{$pluginName}.php");
    }

    private function createComposerProject(): void
    {
        $composerFile = getcwd() . '/composer.json';
        $pluginName = ArgumentManager::getValueByName($this->arguments, "pluginName");
        $author = ArgumentManager::getValueByName($this->arguments, "author");
        $phpVersion = ArgumentManager::getValueByName($this->arguments, "phpVersion");
        $license = ArgumentManager::getValueByName($this->arguments, "phpVersion");

        if (!file_exists($composerFile)) {
            shell_exec("composer init --name {$pluginName} --author \"{$author}\" --type wordpress-plugin --require php:^{$phpVersion} --stability dev --license {$license}");
        }
    }

    private function changeComposerJson(): void
    {
        $composerJsonPath = getcwd() . '/composer.json';
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
        if (!file_exists($path)) {
            mkdir(getcwd() . '/' . $path, 0777, true);
        }
    }
}
