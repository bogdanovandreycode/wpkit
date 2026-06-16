<?php

declare(strict_types=1);

namespace Wpkit\Controller;

use Wpkit\Model\ScaffoldDefinition;
use Wpkit\Model\ScaffoldFieldModel;

final class ScaffoldCatalog
{
    /**
     * @return ScaffoldDefinition[]
     */
    public static function makeDefinitions(): array
    {
        return [
            self::makeRoute(),
            self::makeParam(),
            self::makeAction(),
            self::makeAjax(),
            self::makeCron(),
            self::makeFilter(),
            self::makeAdminPage(),
            self::makePageAlias(),
            self::makeMetaBox(),
            self::makePost(),
            self::makeShortcode(),
            self::makeWidget(),
        ];
    }

    public static function routeCreate(): ScaffoldDefinition
    {
        return new ScaffoldDefinition(
            'route:create',
            'Create REST API RouteController class.',
            'route.template',
            'Http\\Routes',
            'PingRoute',
            self::routeFields(),
        );
    }

    public static function makeRoute(): ScaffoldDefinition
    {
        return new ScaffoldDefinition(
            'make:route',
            'Create a REST route controller scaffold.',
            'route.template',
            'Http\\Routes',
            'PingRoute',
            self::routeFields(),
        );
    }

    public static function makeParam(): ScaffoldDefinition
    {
        return new ScaffoldDefinition(
            'make:param',
            'Create a REST route param scaffold.',
            'param.template',
            'Http\\Params',
            'PostIdParam',
            [
                new ScaffoldFieldModel('paramName', 'Parameter name', 'string', true),
                new ScaffoldFieldModel('default', 'Default value as PHP code', 'code', false, 'null'),
                new ScaffoldFieldModel('required', 'Required flag', 'bool', false, 'true'),
            ],
        );
    }

    public static function makeAction(): ScaffoldDefinition
    {
        return new ScaffoldDefinition(
            'make:action',
            'Create an action controller scaffold.',
            'action.template',
            'Hooks',
            'RegisterSomethingAction',
            [
                new ScaffoldFieldModel('hookName', 'Hook name', 'string', true, 'init'),
                new ScaffoldFieldModel('priority', 'Priority', 'int', false, '10'),
                new ScaffoldFieldModel('acceptedArgs', 'Accepted args', 'int', false, '1'),
            ],
        );
    }

    public static function makeAjax(): ScaffoldDefinition
    {
        return new ScaffoldDefinition(
            'make:ajax',
            'Create an AJAX controller scaffold.',
            'ajax.template',
            'Http\\Ajax',
            'SyncAjax',
            [
                new ScaffoldFieldModel('action', 'AJAX action name', 'string', true, 'demo_sync'),
                new ScaffoldFieldModel('allowGuests', 'Allow guests', 'bool', false, 'false'),
            ],
        );
    }

    public static function makeCron(): ScaffoldDefinition
    {
        return new ScaffoldDefinition(
            'make:cron',
            'Create a cron controller scaffold.',
            'cron.template',
            'Hooks',
            'HourlySyncCron',
            [
                new ScaffoldFieldModel('hookName', 'Cron hook name', 'string', true, 'demo_hourly_sync'),
                new ScaffoldFieldModel('recurrence', 'Recurrence', 'string', true, 'hourly'),
                new ScaffoldFieldModel('startTimestamp', 'Start timestamp as PHP code', 'code', false, 'null'),
            ],
        );
    }

    public static function makeFilter(): ScaffoldDefinition
    {
        return new ScaffoldDefinition(
            'make:filter',
            'Create a filter controller scaffold.',
            'filter.template',
            'Hooks',
            'TitleFilter',
            [
                new ScaffoldFieldModel('hookName', 'Hook name', 'string', true, 'the_title'),
                new ScaffoldFieldModel('priority', 'Priority', 'int', false, '10'),
                new ScaffoldFieldModel('acceptedArgs', 'Accepted args', 'int', false, '1'),
            ],
        );
    }

    public static function makeAdminPage(): ScaffoldDefinition
    {
        return new ScaffoldDefinition(
            'make:admin-page',
            'Create an admin page scaffold.',
            'page.template',
            'Admin',
            'SettingsPage',
            [
                new ScaffoldFieldModel('pageTitle', 'Page title', 'string', true, 'Demo Settings'),
                new ScaffoldFieldModel('menuTitle', 'Menu title', 'string', true, 'Demo Settings'),
                new ScaffoldFieldModel('role', 'Capability / role', 'string', true, 'manage_options'),
                new ScaffoldFieldModel('slug', 'Page slug', 'string', true, 'demo-settings'),
                new ScaffoldFieldModel('position', 'Menu position', 'int', false, '25'),
                new ScaffoldFieldModel('isSubMenuItem', 'Is submenu item', 'bool', false, 'false'),
                new ScaffoldFieldModel(
                    'parentUrl',
                    'Parent slug or null',
                    'code',
                    false,
                    'null',
                    [self::class, 'normalizeNullableStringCode']
                ),
                new ScaffoldFieldModel(
                    'icon',
                    'Dashicon or null',
                    'code',
                    false,
                    'null',
                    [self::class, 'normalizeNullableStringCode']
                ),
            ],
        );
    }

    public static function makePageAlias(): ScaffoldDefinition
    {
        return new ScaffoldDefinition(
            'make:page',
            'Alias of make:admin-page for backward compatibility.',
            'page.template',
            'Admin',
            'SettingsPage',
            [
                new ScaffoldFieldModel('pageTitle', 'Page title', 'string', true, 'Demo Settings'),
                new ScaffoldFieldModel('menuTitle', 'Menu title', 'string', true, 'Demo Settings'),
                new ScaffoldFieldModel('role', 'Capability / role', 'string', true, 'manage_options'),
                new ScaffoldFieldModel('slug', 'Page slug', 'string', true, 'demo-settings'),
                new ScaffoldFieldModel('position', 'Menu position', 'int', false, '25'),
                new ScaffoldFieldModel('isSubMenuItem', 'Is submenu item', 'bool', false, 'false'),
                new ScaffoldFieldModel(
                    'parentUrl',
                    'Parent slug or null',
                    'code',
                    false,
                    'null',
                    [self::class, 'normalizeNullableStringCode']
                ),
                new ScaffoldFieldModel(
                    'icon',
                    'Dashicon or null',
                    'code',
                    false,
                    'null',
                    [self::class, 'normalizeNullableStringCode']
                ),
            ],
        );
    }

    public static function makeMetaBox(): ScaffoldDefinition
    {
        return new ScaffoldDefinition(
            'make:metabox',
            'Create a meta box controller scaffold.',
            'metabox.template',
            'Admin',
            'DemoMetaBox',
            [
                new ScaffoldFieldModel('id', 'Meta box ID', 'string', true, 'demo_box'),
                new ScaffoldFieldModel('title', 'Meta box title', 'string', true, 'Demo Box'),
                new ScaffoldFieldModel('postName', 'Post type', 'string', true, 'post'),
                new ScaffoldFieldModel(
                    'context',
                    'Context (NORMAL, SIDE, ADVANCED)',
                    'code',
                    false,
                    'ADVANCED',
                    [self::class, 'normalizeUppercaseConstant']
                ),
                new ScaffoldFieldModel(
                    'priority',
                    'Priority (HIGH, CORE, DEFAULT, LOW)',
                    'code',
                    false,
                    'DEFAULT',
                    [self::class, 'normalizeUppercaseConstant']
                ),
            ],
        );
    }

    public static function makePost(): ScaffoldDefinition
    {
        return new ScaffoldDefinition(
            'make:post',
            'Create a custom post type scaffold.',
            'post.template',
            'PostTypes',
            'BookPost',
            [
                new ScaffoldFieldModel('postType', 'Post type slug', 'string', true, 'book'),
                new ScaffoldFieldModel('title', 'Post type title', 'string', true, 'Books'),
                new ScaffoldFieldModel('icon', 'Dashicon', 'string', false, 'dashicons-book'),
                new ScaffoldFieldModel('role', 'Capability / role', 'string', false, 'manage_options'),
                new ScaffoldFieldModel(
                    'supports',
                    'Supports as PHP array code',
                    'code',
                    false,
                    "['title', 'editor', 'thumbnail']"
                ),
                new ScaffoldFieldModel('public', 'Public post type', 'bool', false, 'true'),
                new ScaffoldFieldModel('rest', 'Expose in REST API', 'bool', false, 'true'),
                new ScaffoldFieldModel('position', 'Menu position', 'int', false, '20'),
            ],
        );
    }

    public static function makeShortcode(): ScaffoldDefinition
    {
        return new ScaffoldDefinition(
            'make:shortcode',
            'Create a shortcode controller scaffold.',
            'shortcode.template',
            'Shortcodes',
            'BadgeShortcode',
            [
                new ScaffoldFieldModel('name', 'Shortcode name', 'string', true, 'demo_badge'),
                new ScaffoldFieldModel('atts', 'Default atts as PHP array code', 'code', false, "['label' => 'Default Badge']"),
            ],
        );
    }

    public static function makeWidget(): ScaffoldDefinition
    {
        return new ScaffoldDefinition(
            'make:widget',
            'Create a widget controller scaffold.',
            'widget.template',
            'Widgets',
            'DemoWidget',
            [
                new ScaffoldFieldModel('idBase', 'Widget base ID', 'string', true, 'demo_widget'),
                new ScaffoldFieldModel('name', 'Widget title', 'string', true, 'Demo Widget'),
                new ScaffoldFieldModel('description', 'Widget description', 'string', false, 'Simple demo widget'),
            ],
        );
    }

    /**
     * @return ScaffoldFieldModel[]
     */
    private static function routeFields(): array
    {
        return [
            new ScaffoldFieldModel(
                'routeNamespace',
                'Route namespace',
                'string',
                true,
                static fn (string $projectRoot): string => ProjectContextResolver::detectPluginSlug($projectRoot) . '/v1'
            ),
            new ScaffoldFieldModel('routePath', 'Route path', 'string', true, '/ping'),
            new ScaffoldFieldModel('methods', 'HTTP methods', 'string', false, 'GET'),
            new ScaffoldFieldModel('params', 'Route params as PHP array code', 'code', false, '[]'),
            new ScaffoldFieldModel('override', 'Override existing route', 'bool', false, 'false'),
        ];
    }

    public static function normalizeNullableStringCode(string $value): string
    {
        $value = trim($value);

        if ($value === '' || strtolower($value) === 'null') {
            return 'null';
        }

        return var_export($value, true);
    }

    public static function normalizeUppercaseConstant(string $value): string
    {
        return strtoupper(trim($value));
    }
}
