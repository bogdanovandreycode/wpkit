# wpkit Scaffolds Skill

Use this when changing `make:*` commands, scaffold prompts, generated controller classes, or scaffold templates.

Read these files first:

- `src/Controller/ScaffoldCatalog.php`
- `src/Controller/ScaffoldGenerator.php`
- `src/Command/ScaffoldMakeCommand.php`
- relevant templates in `src/Template`

Scaffold flow:

1. `ScaffoldCatalog` defines command name, description, template, namespace suffix, default class, and fields.
2. `ScaffoldMakeCommand` asks questions and normalizes values.
3. `ScaffoldGenerator` resolves target path from Composer PSR-4 namespace and renders the template.

Existing scaffold commands:

- `make:route`
- `make:param`
- `make:action`
- `make:ajax`
- `make:cron`
- `make:filter`
- `make:admin-page`
- `make:page`
- `make:metabox`
- `make:post`
- `make:shortcode`
- `make:widget`

Implementation rules:

- Preserve backward compatibility for existing command names.
- Use PHP code fields for values that must not be quoted twice.
- Use string fields for values that should become PHP string literals.
- Keep generated namespaces under the plugin root namespace.
