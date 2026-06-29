# AI Context

This file is the recommended first read for AI assistants working with wpkit.

## What wpkit Does

wpkit is a PHP CLI tool for scaffolding WordPress plugins that use `arraydev/wptoolkit`.

Primary tasks:

- create plugin projects
- generate common WordPress controller classes
- manage PHP-file translation dictionaries for `WpToolKit\Manager\LocaleManager`
- prepare tests
- build production artifacts
- inspect and edit workspaces through local `ai:*` helper commands
- optionally scope `WpToolKit` into a plugin namespace to avoid cross-plugin version conflicts

## Minimal File Map

- `wpkit`: CLI entrypoint.
- `src/Command`: Symfony Console commands.
- `src/Controller/PluginGenerator.php`: plugin scaffold generator.
- `src/Controller/ScaffoldCatalog.php`: definitions for `make:*` commands.
- `src/Controller/ScaffoldGenerator.php`: turns scaffold definitions into files.
- `src/Command/ProjectBuildCommand.php`: build pipeline.
- `src/Controller/LocaleTranslationController.php`: PHP translation dictionary operations.
- `src/Template`: all generated file templates.
- `plugin-config-sample.json`: sample config for `plugin:create-from-file`.
- `docs/COMMANDS.md`: complete command reference.
- `docs/ai-skills`: compact task-specific AI skill files.

## Task Routing

Use `docs/ai-skills/wpkit-plugin-generation/SKILL.md` for plugin creation or demo scaffold changes.

Use `docs/ai-skills/wpkit-scaffolds/SKILL.md` for `make:*` commands and template work.

Use `docs/ai-skills/wpkit-build/SKILL.md` for `.build.json`, `.buildignore`, Composer, tests, ZIP, and vendor scoping.

Use `docs/ai-skills/wpkit-docs/SKILL.md` for documentation updates.

Use `docs/COMMANDS.md` when command names, arguments, options, aliases, or examples need to stay synchronized.

## Important Conventions

- Generated plugin classes use the plugin namespace from `composer.json`.
- `WpToolKit` references are normal in source templates.
- Build vendor scoping rewrites `WpToolKit` references only in the build artifact.
- `.buildignore` controls copy exclusions only.
- `.build.json` controls build behavior.
- New plugins should include PHPUnit setup by default.
- Demo mode is optional and controlled by `demo` config or `--demo`.
- Translation dictionaries live under `languages/<locale>/<dictionary>.php` by default and return PHP arrays.
