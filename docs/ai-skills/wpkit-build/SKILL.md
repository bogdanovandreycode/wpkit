# wpkit Build Skill

Use this when changing `project:build`, `.buildignore`, `.build.json`, Composer build steps, tests, ZIP behavior, or vendor scoping.

Read these files first:

- `src/Command/ProjectBuildCommand.php`
- `src/Template/.buildignore.template`
- `src/Template/.build.json.template`
- `docs/BUILD.md`

Build pipeline:

1. Copy project files to `build/<plugin>`.
2. Respect `.buildignore`.
3. Never copy source `vendor`.
4. Always copy `composer.json` and `composer.lock`.
5. Optionally run Composer install with dev dependencies.
6. Optionally run tests.
7. Abort on test failure.
8. Optionally rebuild production vendor with `--no-dev`.
9. Process vendor as `scoped`, `standard`, or `none`.
10. Optionally remove composer files.
11. Optionally create ZIP.
12. Optionally remove unzipped build dir.

Vendor scoping:

- `WpToolKit` becomes `<PluginNamespace>\Vendor\WpToolKit`.
- Default scoped directory is `vendor_scoped`.
- Source project files are not rewritten; only build artifacts are rewritten.

Implementation rules:

- Do not copy source `vendor`.
- Do not remove build dir unless ZIP is enabled.
- Keep `.buildignore` focused on copy exclusions.
- Keep `.build.json` focused on pipeline behavior.
