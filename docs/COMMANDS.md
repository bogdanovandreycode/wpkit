# Command Reference

This file lists project commands registered by `wpkit`. Symfony Console also provides built-in `list`, `help`, and `completion` commands.

Run the current command list:

```bash
php wpkit list
```

## Plugin Creation

### `plugin:create`

Creates a new WordPress plugin structure interactively.

```bash
php wpkit plugin:create
php wpkit plugin:create --install
php wpkit plugin:create --demo
```

Options:

- `--install`, `-i`: run `composer install` inside the generated plugin.
- `--demo`: generate demo scaffolds regardless of the interactive demo answer.

### `plugin:create-from-file`

Creates a plugin from a JSON config file.

```bash
php wpkit plugin:create-from-file plugin-config.json
php wpkit plugin:create-from-file plugin-config.json --install
php wpkit plugin:create-from-file plugin-config.json --demo
```

Arguments:

- `config`: path to the JSON configuration file.

Options:

- `--install`, `-i`: run `composer install` inside the generated plugin.
- `--demo`: generate demo scaffolds regardless of JSON config.

### `plugin:init-config`

Copies `plugin-config-sample.json` into the current directory as `plugin-config.json`.

```bash
php wpkit plugin:init-config
php wpkit plugin:init-config --force
```

Options:

- `--force`, `-f`: overwrite an existing `plugin-config.json`.

## Scaffolds

Scaffold commands create runtime classes inside an existing plugin. Each command accepts an optional `name` argument. When omitted, wpkit asks for it interactively.

```bash
php wpkit make:route PingRoute
php wpkit make:param PostIdParam
php wpkit make:action RegisterSomethingAction
php wpkit make:ajax SyncAjax
php wpkit make:cron HourlySyncCron
php wpkit make:filter TitleFilter
php wpkit make:admin-page SettingsPage
php wpkit make:page SettingsPage
php wpkit make:metabox DemoMetaBox
php wpkit make:post BookPost
php wpkit make:shortcode BadgeShortcode
php wpkit make:widget DemoWidget
```

Commands:

- `make:route`: REST route controller in `src/Http/Routes`.
- `make:param`: REST route parameter class in `src/Http/Params`.
- `make:action`: action hook controller in `src/Hooks`.
- `make:ajax`: AJAX controller in `src/Http/Ajax`.
- `make:cron`: cron controller in `src/Hooks`.
- `make:filter`: filter hook controller in `src/Hooks`.
- `make:admin-page`: admin page controller in `src/Admin`.
- `make:page`: backward-compatible admin page command.
- `make:metabox`: meta box controller in `src/Admin`.
- `make:post`: custom post type controller in `src/PostTypes`.
- `make:shortcode`: shortcode controller in `src/Shortcodes`.
- `make:widget`: widget controller in `src/Widgets`.

### `route:create`

Compatibility command for generating REST route controllers.

```bash
php wpkit route:create PingRoute
```

Prefer `make:route` for new usage.

## Translations

Translation commands manage PHP dictionaries for `WpToolKit\Manager\LocaleManager`.

Default layout:

```text
languages/
  en/
    payment.php
  ro/
    payment.php
```

### `language:add`

Creates a locale directory.

```bash
php wpkit language:add en
php wpkit language:add ro --base=resources/lang
```

Arguments:

- `locale`: locale directory name, for example `en`, `ro`, or `ru`.

Options:

- `--base`, `-b`: translation base directory. Default: `languages`.

### `language:remove`

Removes a locale directory and its dictionaries.

```bash
php wpkit language:remove en
php wpkit language:delete en
```

Aliases:

- `language:delete`

Arguments and options match `language:add`.

### `locale:add`

Adds a translation value for one locale. The locale directory must already exist.

```bash
php wpkit locale:add en payment.maib_payment "Payment via MAIB"
php wpkit translation:add en payment.maib_payment "Payment via MAIB"
php wpkit locale:add en first_name_required "First name is required" --dictionary=request.entity.passenger
```

Aliases:

- `translation:add`

Arguments:

- `locale`: target locale.
- `address`: dotted translation address. Without `--dictionary`, the first segment is used as the dictionary file for new entries.
- `value`: translation value.

Options:

- `--base`, `-b`: translation base directory. Default: `languages`.
- `--dictionary`, `-d`: dictionary path when `address` is only a key or nested key inside a dictionary.

### `locale:update`

Updates an existing translation value.

```bash
php wpkit locale:update en payment.maib_payment "MAIB payment"
php wpkit translation:update en payment.maib_payment "MAIB payment"
```

Aliases:

- `translation:update`

Arguments and options match `locale:add`.

### `locale:remove`

Removes a translation value from one locale.

```bash
php wpkit locale:remove en payment.maib_payment
php wpkit locale:delete en payment.maib_payment
php wpkit translation:remove en payment.maib_payment
php wpkit translation:delete en payment.maib_payment
```

Aliases:

- `locale:delete`
- `translation:remove`
- `translation:delete`

Arguments and options match `locale:add`, except there is no `value` argument.

### `locale:show`

Shows translation values for all locale directories by address.

```bash
php wpkit locale:show payment.maib_payment
php wpkit locale:show payment.maib_payment --json
php wpkit translation:show payment.maib_payment
```

Aliases:

- `translation:show`

Arguments:

- `address`: translation address to read.

Options:

- `--base`, `-b`: translation base directory. Default: `languages`.
- `--dictionary`, `-d`: dictionary path when `address` is only a key or nested key inside a dictionary.
- `--json`: output JSON.

## Build

### `project:build`

Creates a production build from `.buildignore` and `.build.json`.

```bash
php wpkit project:build
php wpkit project:build --vendor-scope=scoped
php wpkit project:build --vendor-scope=standard
php wpkit project:build --vendor-scope=none
php wpkit project:build --zip
```

Options:

- `--zip`, `-z`: create ZIP and remove the unzipped project folder inside `build/`.
- `--vendor-scope`: override `vendor.mode`; accepts `none`, `standard`, or `scoped`.

## Toolkit Maintenance

### `toolkit:namespace-update`

Rewrites `WpToolKit` namespace references in PHP files.

```bash
php wpkit toolkit:namespace-update src v2
php wpkit toolkit:namespace-update src v2 --dry-run
php wpkit toolkit:namespace-update src v2 --backup --extensions=php,inc
```

Arguments:

- `path`: directory to scan.
- `version`: toolkit version namespace segment, for example `v2`.

Options:

- `--dry-run`: show affected files without writing.
- `--backup`: create `.bak` files before modification.
- `--extensions`: comma-separated extension filter. Default: `php`.

## Linting

### `lint:check`

Recursively checks PHP syntax using `php -l`.

```bash
php wpkit lint:check
php wpkit lint:check src
```

Arguments:

- `path`: root directory to scan. Default: current directory.

## AI Workspace Utilities

These commands are local helper tools for reading, writing, searching, indexing, and checking a workspace.

### `ai:status`

Shows git working tree status.

```bash
php wpkit ai:status
php wpkit ai:status --json
```

Options:

- `--json`: output JSON.

### `ai:diff`

Shows git diff for a workspace path.

```bash
php wpkit ai:diff
php wpkit ai:diff src
php wpkit ai:diff --staged
php wpkit ai:diff --summary
```

Arguments:

- `path`: path to diff. Default: current directory.

Options:

- `--staged`: show staged diff.
- `--summary`: show diff stat.

### `ai:find`

Finds files or text in the workspace.

```bash
php wpkit ai:find Locale
php wpkit ai:find Locale --path=src
php wpkit ai:find Command --name
php wpkit ai:find Locale --json
```

Arguments:

- `query`: text or filename fragment.

Options:

- `--path`, `-p`: path to search. Default: current directory.
- `--name`: search filenames only.
- `--max`, `-m`: maximum results. Default: `100`.
- `--json`: output JSON.

### `ai:read`

Reads a workspace file with optional line slicing.

```bash
php wpkit ai:read README.md
php wpkit ai:read README.md --start=10 --lines=20
php wpkit ai:read README.md --json
```

Arguments:

- `path`: file path to read.

Options:

- `--start`, `-s`: start line. Default: `1`.
- `--lines`, `-l`: number of lines to read.
- `--json`: output JSON.

### `ai:write`

Writes a workspace file from an argument or STDIN.

```bash
php wpkit ai:write notes.txt "Hello"
php wpkit ai:write notes.txt "More" --append
php wpkit ai:write notes.txt "Replace" --force
```

Arguments:

- `path`: file path to write.
- `content`: content to write. If omitted, STDIN is used.

Options:

- `--force`, `-f`: overwrite existing files.
- `--append`, `-a`: append instead of overwrite.
- `--no-mkdir`: do not create missing directories.
- `--json`: output JSON.

### `ai:check`

Runs code checks useful before or after AI edits.

```bash
php wpkit ai:check
php wpkit ai:check src
php wpkit ai:check --write-index
```

Arguments:

- `path`: path to check. Default: current directory.

Options:

- `--write-index`: rebuild the AI project index after linting.

### `ai:index-build`

Builds or refreshes the AI project index.

```bash
php wpkit ai:index-build
php wpkit ai:index-build src --output=.wpkit/ai-index.json
php wpkit ai:index-build --json
```

Arguments:

- `path`: path to scan. Default: current directory.

Options:

- `--output`, `-o`: index file path. Default: `.wpkit/ai-index.json`.
- `--json`: output JSON.

### `ai:index-update`

Updates the AI project index after code changes.

```bash
php wpkit ai:index-update
php wpkit ai:index-update src/Command --output=.wpkit/ai-index.json
php wpkit ai:index-update --json
```

Arguments and options match `ai:index-build`.

### `ai:index-show`

Shows the whole AI index or one symbol.

```bash
php wpkit ai:index-show
php wpkit ai:index-show LocaleTranslationController
php wpkit ai:index-show LocaleTranslationController --index=.wpkit/ai-index.json
```

Arguments:

- `symbol`: class short or full name.

Options:

- `--index`, `-i`: index file path. Default: `.wpkit/ai-index.json`.

### `ai:index-remove`

Removes one symbol from the AI project index.

```bash
php wpkit ai:index-remove LocaleTranslationController
php wpkit ai:index-remove LocaleTranslationController --index=.wpkit/ai-index.json
```

Arguments:

- `symbol`: class short or full name to remove.

Options:

- `--index`, `-i`: index file path. Default: `.wpkit/ai-index.json`.
