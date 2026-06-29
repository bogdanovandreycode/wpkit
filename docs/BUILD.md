# Build

`project:build` creates production artifacts from a plugin project.

```bash
php wpkit project:build
```

The build uses two files:

- `.buildignore`: copy exclusions.
- `.build.json`: pipeline settings.

## Pipeline

1. Delete the previous `build` directory.
2. Copy files into `build/<plugin>`.
3. Respect `.buildignore`.
4. Never copy source `vendor`.
5. Always copy `composer.json` and `composer.lock` when present.
6. Optionally run `composer install` with dev dependencies.
7. Optionally run tests.
8. Abort when tests fail.
9. Optionally replace dev vendor with production `composer install --no-dev --optimize-autoloader`.
10. Process vendor according to `vendor.mode`.
11. Optionally remove composer files from the final build.
12. Optionally create ZIP.
13. Optionally remove the unzipped build folder.

## Config Reference

Default `.build.json`:

```json
{
    "composer": {
        "installDev": true,
        "installNoDev": true,
        "keepComposerFiles": false
    },
    "tests": {
        "enabled": true,
        "command": "composer test"
    },
    "vendor": {
        "mode": "scoped",
        "scopedDir": "vendor_scoped",
        "keepOriginal": false
    },
    "zip": {
        "enabled": true
    },
    "cleanup": {
        "removeBuildDir": false
    }
}
```

## Composer Options

`composer.installDev`: run Composer install with dev dependencies before tests.

`composer.installNoDev`: delete dev vendor and run production install after tests.

`composer.keepComposerFiles`: keep `composer.json` and `composer.lock` in the final build.

## Test Options

`tests.enabled`: run tests during build.

`tests.command`: shell command to execute from the build project directory.

If the command exits with a non-zero status, the build stops and no production vendor or ZIP is produced.

## Vendor Options

`vendor.mode` supports:

- `scoped`: move or copy `vendor` into `vendor_scoped` and rewrite `WpToolKit` namespace to `<PluginNamespace>\Vendor\WpToolKit`.
- `standard`: keep production `vendor` unchanged.
- `none`: delete `vendor` from the final build.

`vendor.scopedDir`: directory name for scoped vendor, default `vendor_scoped`.

`vendor.keepOriginal`: when `true`, copy `vendor` to `vendor_scoped` and leave original `vendor`. When `false`, move `vendor` to `vendor_scoped`.

## ZIP And Cleanup

`zip.enabled`: create `build/<plugin>.zip`.

`cleanup.removeBuildDir`: remove `build/<plugin>` after ZIP creation. This requires `zip.enabled=true`.

CLI compatibility overrides:

```bash
php wpkit project:build --vendor-scope=standard
php wpkit project:build --vendor-scope=scoped
php wpkit project:build --vendor-scope=none
php wpkit project:build --zip
```

See [Command Reference](COMMANDS.md#build) for the full CLI entry.
