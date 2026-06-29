# wpkit

`wpkit` is a CLI toolkit for creating WordPress plugins that use `arraydev/wptoolkit`.

It scaffolds plugin structure, Composer metadata, PHPUnit tests, build settings, and common WordPress runtime classes such as REST routes, admin pages, meta boxes, custom post types, hooks, shortcodes, widgets, and AJAX handlers.

## Command Overview

Run the current command list:

```bash
php wpkit list
```

Project command groups:

- `plugin:*`: create plugins from interactive answers or JSON config.
- `make:*` and `route:create`: generate runtime classes inside an existing plugin.
- `language:*`, `locale:*`, and `translation:*`: manage PHP translation dictionaries for `WpToolKit\Manager\LocaleManager`.
- `project:build`: build production artifacts from `.buildignore` and `.build.json`.
- `toolkit:namespace-update`: rewrite `WpToolKit` namespace references.
- `lint:check`: run recursive PHP syntax checks.
- `ai:*`: local workspace helpers for status, diff, search, file IO, checks, and indexing.

Available project commands:

| Group | Commands |
| --- | --- |
| Plugin creation | `plugin:create`, `plugin:create-from-file`, `plugin:init-config` |
| Scaffolds | `make:route`, `make:param`, `make:action`, `make:ajax`, `make:cron`, `make:filter`, `make:admin-page`, `make:page`, `make:metabox`, `make:post`, `make:shortcode`, `make:widget`, `route:create` |
| Translations | `language:add`, `language:remove` / `language:delete`, `locale:add` / `translation:add`, `locale:update` / `translation:update`, `locale:remove` / `locale:delete` / `translation:remove` / `translation:delete`, `locale:show` / `translation:show` |
| Build | `project:build` |
| Toolkit maintenance | `toolkit:namespace-update` |
| Linting | `lint:check` |
| AI helpers | `ai:status`, `ai:diff`, `ai:find`, `ai:read`, `ai:write`, `ai:check`, `ai:index-build`, `ai:index-update`, `ai:index-show`, `ai:index-remove` |

See [Command Reference](docs/COMMANDS.md) for every command, argument, option, and alias.

## Quick Start

Create a plugin interactively:

```bash
php wpkit plugin:create
```

Create a plugin from JSON:

```bash
php wpkit plugin:init-config
php wpkit plugin:create-from-file plugin-config.json
```

Create a plugin with demo scaffolds:

```bash
php wpkit plugin:create --demo
php wpkit plugin:create-from-file plugin-config.json --demo
```

Or set `"demo": true` in `plugin-config.json`.

## Generated Plugin

A new plugin includes:

- main plugin file with WordPress headers
- `src/Boot.php` and `src/Main.php`
- Composer PSR-4 autoloading
- PHPUnit setup with `tests/ExampleTest.php`
- `.buildignore`
- `.build.json`
- default folders for admin, hooks, REST, post types, shortcodes, services, widgets, views, config, assets, and translations

## Translation Commands

`wpkit` can manage PHP-file dictionaries used by `WpToolKit\Manager\LocaleManager`.

```bash
php wpkit language:add en
php wpkit language:remove en

php wpkit locale:add en payment.maib_payment "Payment via MAIB"
php wpkit locale:update en payment.maib_payment "MAIB payment"
php wpkit locale:remove en payment.maib_payment
php wpkit locale:show payment.maib_payment
```

The default base directory is `languages`. Use `--base=resources/lang` for a custom translation root.
Use `--dictionary=request.entity.passenger` when the address is only a key inside a nested dictionary.

When demo mode is enabled, wpkit also generates example classes for:

- custom post type
- meta box
- admin page
- REST route
- REST param
- AJAX handler
- action hook
- shortcode
- widget
- view mapping and view file

## Build

Run:

```bash
php wpkit project:build
```

Build behavior is configured by `.build.json`.

The full pipeline can:

1. Copy project files into `build/<plugin>` while respecting `.buildignore`.
2. Always copy `composer.json` and `composer.lock`.
3. Never copy the source `vendor` folder.
4. Run Composer install with dev dependencies.
5. Run tests.
6. Stop the build when tests fail.
7. Replace dev vendor with production `composer install --no-dev`.
8. Keep, remove, or scope vendor into `vendor_scoped`.
9. Create a ZIP archive.
10. Optionally remove the unzipped build folder.

See [Build](docs/BUILD.md) for the full config reference.

## Documentation

- [Command Reference](docs/COMMANDS.md)
- [Plugin Creation](docs/PLUGIN_CREATION.md)
- [Scaffolds](docs/SCAFFOLDS.md)
- [Build](docs/BUILD.md)
- [AI Context](docs/AI_CONTEXT.md)

For AI assistants, start with [AI Context](docs/AI_CONTEXT.md), then load only the skill file that matches the task from `docs/ai-skills/`.
