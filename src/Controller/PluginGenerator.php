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
        TemplateEngine::generate('.build.json.template', $this->arguments, '.build.json');
        TemplateEngine::generate('phpunit.xml.template', $this->arguments, 'phpunit.xml');
        TemplateEngine::generate('ExampleTest.template', $this->arguments, 'tests/ExampleTest.php');

        if ($this->isDemoEnabled()) {
            $this->generateDemoScaffolds();
        }
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
        ArgumentManager::setValueByName(
            $this->arguments,
            'demo',
            $this->parseBool(ArgumentManager::getValueByName($this->arguments, 'demo')) ? 'true' : 'false'
        );
    }

    private function isDemoEnabled(): bool
    {
        return $this->parseBool(ArgumentManager::getValueByName($this->arguments, 'demo'));
    }

    private function parseBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    private function generateDemoScaffolds(): void
    {
        $namespace = (string) ArgumentManager::getValueByName($this->arguments, 'namespace');
        $pluginSlug = (string) ArgumentManager::getValueByName($this->arguments, 'pluginSlug');

        $this->generateDemoFile('post.template', [
            'namespace' => "{$namespace}\\PostTypes",
            'className' => 'DemoBookPost',
            'postType' => $this->phpString('demo_book'),
            'title' => $this->phpString('Demo Books'),
            'icon' => $this->phpString('dashicons-book'),
            'role' => $this->phpString('manage_options'),
            'supports' => "['title', 'editor', 'thumbnail']",
            'public' => 'true',
            'rest' => 'true',
            'position' => '20',
        ], 'src/PostTypes/DemoBookPost.php');

        $this->generateDemoFile('metabox.template', [
            'namespace' => "{$namespace}\\Admin",
            'className' => 'DemoBookMetaBox',
            'id' => $this->phpString('demo_book_details'),
            'title' => $this->phpString('Demo Book Details'),
            'postName' => $this->phpString('demo_book'),
            'context' => 'ADVANCED',
            'priority' => 'DEFAULT',
        ], 'src/Admin/DemoBookMetaBox.php');

        $this->generateDemoFile('page.template', [
            'namespace' => "{$namespace}\\Admin",
            'className' => 'DemoSettingsPage',
            'pageTitle' => $this->phpString('Demo Settings'),
            'menuTitle' => $this->phpString('Demo Settings'),
            'role' => $this->phpString('manage_options'),
            'slug' => $this->phpString('demo-settings'),
            'position' => '25',
            'isSubMenuItem' => 'false',
            'parentUrl' => 'null',
            'icon' => $this->phpString('dashicons-admin-generic'),
        ], 'src/Admin/DemoSettingsPage.php');

        $this->generateDemoFile('route.template', [
            'namespace' => "{$namespace}\\Http\\Routes",
            'className' => 'DemoPingRoute',
            'routeNamespace' => $this->phpString($pluginSlug . '/v1'),
            'routePath' => $this->phpString('/demo/ping'),
            'methods' => $this->phpString('GET'),
            'params' => '[]',
            'override' => 'false',
        ], 'src/Http/Routes/DemoPingRoute.php');

        $this->generateDemoFile('param.template', [
            'namespace' => "{$namespace}\\Http\\Params",
            'className' => 'DemoIdParam',
            'paramName' => $this->phpString('id'),
            'default' => 'null',
            'required' => 'false',
        ], 'src/Http/Params/DemoIdParam.php');

        $this->generateDemoFile('ajax.template', [
            'namespace' => "{$namespace}\\Http\\Ajax",
            'className' => 'DemoSyncAjax',
            'action' => $this->phpString('demo_sync'),
            'allowGuests' => 'false',
        ], 'src/Http/Ajax/DemoSyncAjax.php');

        $this->generateDemoFile('action.template', [
            'namespace' => "{$namespace}\\Hooks",
            'className' => 'DemoInitAction',
            'hookName' => $this->phpString('init'),
            'priority' => '10',
            'acceptedArgs' => '1',
        ], 'src/Hooks/DemoInitAction.php');

        $this->generateDemoFile('shortcode.template', [
            'namespace' => "{$namespace}\\Shortcodes",
            'className' => 'DemoBadgeShortcode',
            'name' => $this->phpString('demo_badge'),
            'atts' => "['label' => 'Demo Badge']",
        ], 'src/Shortcodes/DemoBadgeShortcode.php');

        $this->generateDemoFile('widget.template', [
            'namespace' => "{$namespace}\\Widgets",
            'className' => 'DemoWidget',
            'idBase' => $this->phpString('demo_widget'),
            'name' => $this->phpString('Demo Widget'),
            'description' => $this->phpString('Simple demo widget'),
        ], 'src/Widgets/DemoWidget.php');

        TemplateEngine::generate('DemoViewsYaml.template', $this->arguments, 'config/views.yaml');
        TemplateEngine::generate('DemoView.template', $this->arguments, 'views/demo.php');
    }

    /**
     * @param array<string, scalar|null> $variables
     */
    private function generateDemoFile(string $templateName, array $variables, string $outputPath): void
    {
        TemplateEngine::generateFromMap($templateName, $variables, $outputPath);
    }

    private function phpString(string $value): string
    {
        return var_export($value, true);
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
            'autoload-dev' => [
                'psr-4' => [
                    "{$namespace}\\Tests\\" => 'tests/'
                ]
            ],
            'require-dev' => [
                'phpunit/phpunit' => '^10.5'
            ],
            'scripts' => [
                'test' => 'phpunit'
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
        $this->makeDirectory('tests');
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
