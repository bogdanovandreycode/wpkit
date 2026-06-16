<?php

declare(strict_types=1);

namespace Wpkit\Controller;

final class PluginMetadata
{
    public static function normalizePluginName(string $value): string
    {
        $value = trim($value);

        return preg_replace('/\s+/', ' ', $value) ?: '';
    }

    public static function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: '';
        $value = trim($value, '-');

        return $value === '' ? 'my-plugin' : $value;
    }

    public static function namespaceify(string $value): string
    {
        $segments = preg_split('/[^a-zA-Z0-9]+/', trim($value)) ?: [];
        $segments = array_filter($segments, static fn (string $segment): bool => $segment !== '');
        $segments = array_map(
            static fn (string $segment): string => ucfirst($segment),
            $segments
        );

        return $segments === [] ? 'MyPlugin' : implode('', $segments);
    }

    public static function normalizeVendor(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: '';
        $value = trim($value, '-');

        return $value === '' ? 'vendor' : $value;
    }

    public static function normalizeDomainPath(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '/languages';
        }

        return '/' . trim($value, '/\\');
    }
}
