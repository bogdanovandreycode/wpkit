# wpkit Plugin Generation Skill

Use this when changing `plugin:create`, `plugin:create-from-file`, generated plugin structure, demo mode, Composer defaults, PHPUnit defaults, or plugin config fields.

Read these files first:

- `src/Controller/PluginGenerator.php`
- `src/Command/PluginCreateCommand.php`
- `src/Command/PluginCreateFromFileCommand.php`
- `plugin-config-sample.json`
- relevant templates in `src/Template`

Core behavior:

- `PluginGenerator` normalizes arguments, creates directories, writes `composer.json`, then renders templates.
- New plugins include `.buildignore`, `.build.json`, `phpunit.xml`, and `tests/ExampleTest.php`.
- Demo mode is enabled by `demo=true` or `--demo`.
- Demo mode creates examples for post type, metabox, admin page, route, param, ajax, action, shortcode, widget, and view.

Implementation rules:

- Keep source templates using `WpToolKit`; build scoping handles namespacing later.
- Add new config fields to both interactive command and `plugin-config-sample.json`.
- Prefer `TemplateEngine::generate` or `generateFromMap`.
- Avoid requiring Composer install unless the user explicitly asks or `--install` is set.
