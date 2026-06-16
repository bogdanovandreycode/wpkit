<?php
declare(strict_types=1);

namespace AwesomePlugin;

use WpToolKit\Controller\ViewLoader;
use WpToolKit\Factory\ServiceFactory;
use WpToolKit\Loader\AttributeLoader;
use WpToolKit\Manager\LifecycleManager;

final class Boot
{
    private string $pluginDir;
    private ServiceFactory $container;
    private ViewLoader $views;
    private LifecycleManager $lifecycle;

    public function __construct(
        private readonly string $pluginFile,
    ) {
        $this->pluginDir = plugin_dir_path($this->pluginFile);
        $this->container = new ServiceFactory();
        $this->views = new ViewLoader();
        $this->lifecycle = new LifecycleManager();

        $this->registerServices();
        self::registerLifecycleCallbacks($this->lifecycle);
        $this->registerHooks();
    }

    private function registerServices(): void
    {
        $this->container->instance(ServiceFactory::class, $this->container);
        $this->container->instance(ViewLoader::class, $this->views);
        $this->container->instance(LifecycleManager::class, $this->lifecycle);
    }

    private static function registerLifecycleCallbacks(LifecycleManager $lifecycle): void
    {
        $lifecycle->onActivate(function (): void {
            // Register activation callbacks here.
        });

        $lifecycle->onDeactivate(function (): void {
            // Register deactivation callbacks here.
        });

        $lifecycle->onUninstall(function (): void {
            // Register uninstall callbacks here.
        });
    }

    private function registerHooks(): void
    {
        add_action('plugins_loaded', [$this, 'onPluginsLoaded']);

        register_activation_hook($this->pluginFile, [$this, 'activate']);
        register_deactivation_hook($this->pluginFile, [$this, 'deactivate']);
        register_uninstall_hook($this->pluginFile, [self::class, 'uninstall']);
    }

    public function onPluginsLoaded(): void
    {
        load_plugin_textdomain(
            'awesome-plugin',
            false,
            dirname(plugin_basename($this->pluginFile)) . '/languages',
        );

        $this->views->loadFromYaml(
            $this->pluginDir . 'config/views.yaml',
            $this->pluginDir,
        );

        $loader = new AttributeLoader(
            'AwesomePlugin',
            $this->pluginDir . 'src',
            $this->container,
        );

        $loader->loadControllers();

        (new Main(
            $this->pluginFile,
            $this->pluginDir,
            $this->container,
            $this->views,
            $this->lifecycle,
        ))->boot();
    }

    public function activate(): void
    {
        $this->lifecycle->activate();
        flush_rewrite_rules();
    }

    public function deactivate(): void
    {
        $this->lifecycle->deactivate();
        flush_rewrite_rules();
    }

    public static function uninstall(): void
    {
        $lifecycle = new LifecycleManager();
        self::registerLifecycleCallbacks($lifecycle);
        $lifecycle->uninstall();
    }
}
