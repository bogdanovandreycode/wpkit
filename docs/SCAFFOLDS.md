# Scaffolds

wpkit can generate individual runtime classes inside an existing plugin. It reads the root namespace from `composer.json` and writes files into the matching `src/` subdirectory.

## Commands

```bash
php wpkit make:route
php wpkit make:param
php wpkit make:action
php wpkit make:ajax
php wpkit make:cron
php wpkit make:filter
php wpkit make:admin-page
php wpkit make:page
php wpkit make:metabox
php wpkit make:post
php wpkit make:shortcode
php wpkit make:widget
```

`route:create` is kept as a compatibility alias for REST routes.

## Scaffolds

`make:route` creates a REST route controller in `src/Http/Routes`.

`make:param` creates a REST route parameter class in `src/Http/Params`.

`make:action` creates an action hook controller in `src/Hooks`.

`make:ajax` creates an AJAX controller in `src/Http/Ajax`.

`make:cron` creates a cron controller in `src/Hooks`.

`make:filter` creates a filter hook controller in `src/Hooks`.

`make:admin-page` and `make:page` create admin page controllers in `src/Admin`.

`make:metabox` creates meta box controllers in `src/Admin`.

`make:post` creates custom post type controllers in `src/PostTypes`.

`make:shortcode` creates shortcode controllers in `src/Shortcodes`.

`make:widget` creates widget controllers in `src/Widgets`.

## Runtime Loading

Generated plugin `Boot.php` loads:

- text domain
- YAML view mappings from `config/views.yaml`
- attribute-based controllers under `src`
- conventional post type controllers under `src/PostTypes`
- the plugin `Main` class

Most scaffold classes are discovered by `WpToolKit\Loader\AttributeLoader`.
Post type classes are loaded conventionally by parent class.
