# Plugin Creation

wpkit creates a WordPress plugin directory from interactive answers or a JSON config file.

## Commands

Interactive:

```bash
php wpkit plugin:create
```

From JSON:

```bash
php wpkit plugin:create-from-file plugin-config.json
```

Create a sample JSON config:

```bash
php wpkit plugin:init-config
```

Install dependencies immediately after scaffolding:

```bash
php wpkit plugin:create --install
php wpkit plugin:create-from-file plugin-config.json --install
```

Generate demo scaffolds:

```bash
php wpkit plugin:create --demo
php wpkit plugin:create-from-file plugin-config.json --demo
```

See [Command Reference](COMMANDS.md#plugin-creation) for all plugin command arguments and options.

## Config Fields

`plugin-config.json` supports:

- `pluginName`: WordPress display name.
- `pluginSlug`: plugin directory and main file slug.
- `vendor`: Composer vendor name.
- `author`: plugin author.
- `authorURI`: author URL.
- `namespace`: root PHP namespace.
- `description`: plugin description.
- `pluginURI`: plugin URL.
- `version`: plugin version.
- `phpVersion`: minimum PHP version.
- `license`: license identifier.
- `licenseURI`: license URL.
- `textDomain`: WordPress text domain.
- `domainPath`: translation directory.
- `demo`: boolean flag for demo scaffolds.

## Generated Files

Every plugin includes:

- `<pluginSlug>.php`
- `composer.json`
- `.buildignore`
- `.build.json`
- `phpunit.xml`
- `tests/ExampleTest.php`
- `src/Boot.php`
- `src/Main.php`
- `config/views.yaml`
- standard source directories under `src/`

## Demo Mode

Demo mode generates a small working map of wpkit concepts:

- `src/PostTypes/DemoBookPost.php`
- `src/Admin/DemoBookMetaBox.php`
- `src/Admin/DemoSettingsPage.php`
- `src/Http/Routes/DemoPingRoute.php`
- `src/Http/Params/DemoIdParam.php`
- `src/Http/Ajax/DemoSyncAjax.php`
- `src/Hooks/DemoInitAction.php`
- `src/Shortcodes/DemoBadgeShortcode.php`
- `src/Widgets/DemoWidget.php`
- `config/views.yaml`
- `views/demo.php`

Use demo mode when starting a new plugin, teaching wpkit, or giving an AI assistant enough examples to continue development quickly.
