<?php

declare(strict_types=1);

namespace Wpkit\Controller;

use InvalidArgumentException;

final class WorkspacePath
{
    public static function root(?string $root = null): string
    {
        $root ??= getcwd();
        $realRoot = realpath($root);

        if ($realRoot === false) {
            throw new InvalidArgumentException("Workspace root not found: {$root}");
        }

        return rtrim(str_replace('\\', '/', $realRoot), '/');
    }

    public static function resolve(string $path, ?string $root = null, bool $mustExist = false): string
    {
        $rootPath = self::root($root);
        $candidate = self::isAbsolute($path)
            ? $path
            : $rootPath . '/' . ltrim($path, '/\\');

        $normalized = self::normalize($candidate);
        $real = realpath($normalized);

        if ($real !== false) {
            $normalized = self::normalize($real);
        } elseif ($mustExist) {
            throw new InvalidArgumentException("Path not found: {$path}");
        }

        if ($normalized !== $rootPath && !str_starts_with($normalized, $rootPath . '/')) {
            throw new InvalidArgumentException("Path is outside workspace: {$path}");
        }

        return $normalized;
    }

    public static function relative(string $path, ?string $root = null): string
    {
        $rootPath = self::root($root);
        $path = self::normalize($path);

        if ($path === $rootPath) {
            return '.';
        }

        return ltrim(substr($path, strlen($rootPath)), '/');
    }

    private static function isAbsolute(string $path): bool
    {
        return preg_match('/^[a-zA-Z]:[\/\\\\]/', $path) === 1
            || str_starts_with($path, '/')
            || str_starts_with($path, '\\');
    }

    private static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $prefix = '';

        if (preg_match('/^[a-zA-Z]:/', $path, $match) === 1) {
            $prefix = $match[0];
            $path = substr($path, strlen($prefix));
        }

        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..') {
                array_pop($parts);
                continue;
            }

            $parts[] = $part;
        }

        return $prefix . '/' . implode('/', $parts);
    }
}
