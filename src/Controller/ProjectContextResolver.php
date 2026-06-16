<?php

declare(strict_types=1);

namespace Wpkit\Controller;

final class ProjectContextResolver
{
    public static function detectBaseNamespace(?string $projectRoot = null): ?string
    {
        $projectRoot ??= getcwd();
        $composerJson = $projectRoot . '/composer.json';

        if (!file_exists($composerJson)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($composerJson), true);
        if (!is_array($data)) {
            return null;
        }

        $psr4 = $data['autoload']['psr-4'] ?? null;
        if (!is_array($psr4) || $psr4 === []) {
            return null;
        }

        return rtrim((string) array_key_first($psr4), '\\');
    }

    public static function buildDefaultNamespace(string $suffix, ?string $projectRoot = null): string
    {
        $baseNamespace = self::detectBaseNamespace($projectRoot) ?? 'Plugin';

        return $baseNamespace . '\\' . trim($suffix, '\\');
    }

    public static function resolveTargetPath(
        string $namespace,
        string $className,
        ?string $projectRoot = null
    ): string {
        $projectRoot ??= getcwd();
        $baseNamespace = self::detectBaseNamespace($projectRoot);
        $relativeNamespace = trim($namespace, '\\');

        if (
            $baseNamespace !== null
            && str_starts_with($relativeNamespace, $baseNamespace . '\\')
        ) {
            $relativeNamespace = substr($relativeNamespace, strlen($baseNamespace) + 1);
        }

        $relativePath = 'src/' . str_replace('\\', '/', $relativeNamespace);

        return $projectRoot . '/' . trim($relativePath, '/') . '/' . $className . '.php';
    }

    public static function detectPluginSlug(?string $projectRoot = null): string
    {
        $projectRoot ??= getcwd();
        $composerJson = $projectRoot . '/composer.json';

        if (file_exists($composerJson)) {
            $data = json_decode((string) file_get_contents($composerJson), true);

            if (is_array($data) && isset($data['name']) && is_string($data['name'])) {
                $parts = explode('/', $data['name']);

                return trim((string) end($parts));
            }
        }

        return basename($projectRoot);
    }
}
