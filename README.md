# wpkit

`wpkit` is a CLI toolkit for creating WordPress plugins that use `arraydev/wptoolkit`.

It scaffolds plugin structure, Composer metadata, PHPUnit tests, build settings, and common WordPress runtime classes such as REST routes, admin pages, meta boxes, custom post types, hooks, shortcodes, widgets, and AJAX handlers.

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

- [Plugin Creation](docs/PLUGIN_CREATION.md)
- [Scaffolds](docs/SCAFFOLDS.md)
- [Build](docs/BUILD.md)
- [AI Context](docs/AI_CONTEXT.md)

For AI assistants, start with [AI Context](docs/AI_CONTEXT.md), then load only the skill file that matches the task from `docs/ai-skills/`.
