<?php

declare(strict_types=1);

namespace Wpkit\Controller;

use InvalidArgumentException;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

final class ProjectIndexController
{
    private const DEFAULT_INDEX_PATH = '.wpkit/ai-index.json';
    private const EXCLUDES = ['.git', 'vendor', 'node_modules', 'build', 'dist', '.wpkit'];

    /**
     * @return array<string, mixed>
     */
    public function build(string $path = '.', ?string $output = null): array
    {
        $root = WorkspacePath::root();
        $scanPath = WorkspacePath::resolve($path, mustExist: true);
        $indexPath = WorkspacePath::resolve($output ?? self::DEFAULT_INDEX_PATH);

        $classes = [];
        foreach ($this->phpFiles($scanPath) as $file) {
            foreach ($this->scanFile($file, $root) as $class) {
                $classes[$class['name']] = $class;
            }
        }

        ksort($classes);

        $index = [
            'schema' => 1,
            'generatedAt' => date(DATE_ATOM),
            'root' => basename($root),
            'classCount' => count($classes),
            'classes' => array_values($classes),
        ];

        $directory = dirname($indexPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents(
            $indexPath,
            json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $index + ['indexPath' => WorkspacePath::relative($indexPath)];
    }

    /**
     * @return array<string, mixed>
     */
    public function load(?string $indexPath = null): array
    {
        $file = WorkspacePath::resolve($indexPath ?? self::DEFAULT_INDEX_PATH, mustExist: true);
        $data = json_decode((string) file_get_contents($file), true);

        if (!is_array($data)) {
            throw new InvalidArgumentException("Invalid index file: {$file}");
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function show(?string $symbol = null, ?string $indexPath = null): array
    {
        $index = $this->load($indexPath);

        if ($symbol === null || $symbol === '') {
            return [
                'schema' => $index['schema'] ?? null,
                'generatedAt' => $index['generatedAt'] ?? null,
                'classCount' => $index['classCount'] ?? 0,
                'classes' => array_map(
                    static fn (array $class): array => [
                        'name' => $class['name'] ?? '',
                        'path' => $class['path'] ?? '',
                        'description' => $class['technicalDescription'] ?? '',
                    ],
                    $index['classes'] ?? []
                ),
            ];
        }

        foreach (($index['classes'] ?? []) as $class) {
            if (($class['name'] ?? '') === $symbol || str_ends_with((string) ($class['name'] ?? ''), '\\' . $symbol)) {
                return $class;
            }
        }

        throw new InvalidArgumentException("Symbol not found in index: {$symbol}");
    }

    /**
     * @return array<string, mixed>
     */
    public function remove(string $symbol, ?string $indexPath = null): array
    {
        $file = WorkspacePath::resolve($indexPath ?? self::DEFAULT_INDEX_PATH, mustExist: true);
        $index = $this->load($indexPath);
        $before = count($index['classes'] ?? []);

        $index['classes'] = array_values(array_filter(
            $index['classes'] ?? [],
            static fn (array $class): bool => ($class['name'] ?? '') !== $symbol
                && !str_ends_with((string) ($class['name'] ?? ''), '\\' . $symbol)
        ));
        $index['classCount'] = count($index['classes']);
        $index['generatedAt'] = date(DATE_ATOM);

        file_put_contents($file, json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return [
            'removed' => $before - count($index['classes']),
            'classCount' => $index['classCount'],
            'indexPath' => WorkspacePath::relative($file),
        ];
    }

    /**
     * @return string[]
     */
    private function phpFiles(string $root): array
    {
        if (is_file($root)) {
            return strtolower(pathinfo($root, PATHINFO_EXTENSION)) === 'php' ? [$root] : [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
                function ($current): bool {
                    return !$current->isDir()
                        || !in_array($current->getFilename(), self::EXCLUDES, true);
                }
            ),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $files[] = str_replace('\\', '/', $file->getPathname());
            }
        }

        sort($files);

        return $files;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function scanFile(string $file, string $root): array
    {
        $tokens = token_get_all((string) file_get_contents($file));
        $namespace = '';
        $classes = [];
        $active = null;
        $pendingClass = null;
        $lastDoc = null;
        $braceDepth = 0;
        $tokenCount = count($tokens);

        for ($i = 0; $i < $tokenCount; $i++) {
            $token = $tokens[$i];

            if (is_array($token)) {
                if ($token[0] === T_DOC_COMMENT) {
                    $lastDoc = $token[1];
                    continue;
                }

                if ($token[0] === T_NAMESPACE) {
                    $namespace = $this->readNamespace($tokens, $i + 1);
                    continue;
                }

                if (in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT], true)) {
                    if (in_array($this->previousMeaningfulToken($tokens, $i), [T_NEW, T_DOUBLE_COLON], true)) {
                        continue;
                    }

                    $pendingClass = $this->readClass($tokens, $i, $namespace, $lastDoc, $file, $root);
                    $lastDoc = null;
                    continue;
                }

                if ($active !== null && $token[0] === T_FUNCTION) {
                    $method = $this->readMethod($tokens, $i, $lastDoc);
                    if ($method !== null) {
                        $active['class']['methods'][] = $method;
                        $lastDoc = null;
                    }
                    continue;
                }

                if ($active !== null && $braceDepth === $active['depth']) {
                    if ($token[0] === T_VARIABLE && $this->isPropertyToken($tokens, $i)) {
                        $active['class']['properties'][] = $this->readProperty($tokens, $i, $lastDoc);
                        $lastDoc = null;
                        continue;
                    }
                }

                continue;
            }

            if ($token === '{') {
                $braceDepth++;

                if ($pendingClass !== null) {
                    $pendingClass['depth'] = $braceDepth;
                    $active = ['depth' => $braceDepth, 'class' => $pendingClass];
                    $pendingClass = null;
                }

                continue;
            }

            if ($token === '}') {
                if ($this->isStringInterpolationClose($tokens, $i)) {
                    continue;
                }

                $braceDepth--;

                if ($active !== null && $braceDepth < $active['depth']) {
                    $classes[] = $active['class'];
                    $active = null;
                }
            }
        }

        return $classes;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function readNamespace(array $tokens, int $start): string
    {
        $namespace = '';

        for ($i = $start, $max = count($tokens); $i < $max; $i++) {
            $token = $tokens[$i];

            if ($token === ';' || $token === '{') {
                break;
            }

            $namespace .= is_array($token) ? $token[1] : $token;
        }

        return trim($namespace);
    }

    /**
     * @param array<int, mixed> $tokens
     * @return array<string, mixed>
     */
    private function readClass(array $tokens, int $index, string $namespace, ?string $doc, string $file, string $root): array
    {
        $name = '';
        $line = is_array($tokens[$index]) ? $tokens[$index][2] : 0;
        $type = strtolower(str_replace('T_', '', token_name($tokens[$index][0])));

        for ($i = $index + 1, $max = count($tokens); $i < $max; $i++) {
            if (!is_array($tokens[$i]) && $tokens[$i] === '(') {
                return null;
            }

            if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                $name = $tokens[$i][1];
                break;
            }
        }

        $signature = $this->collectUntil($tokens, $i + 1, '{');
        preg_match('/extends\s+([^\s{]+)/', $signature, $extends);
        preg_match('/implements\s+(.+)$/', $signature, $implements);

        $fullName = ltrim($namespace . '\\' . $name, '\\');

        return [
            'name' => $fullName,
            'shortName' => $name,
            'type' => $type,
            'path' => WorkspacePath::relative($file, $root),
            'line' => $line,
            'extends' => $extends[1] ?? null,
            'implements' => isset($implements[1])
                ? array_map('trim', explode(',', $implements[1]))
                : [],
            'technicalDescription' => $this->descriptionFromDoc($doc)
                ?: "{$type} {$fullName}",
            'usageDescription' => $this->usageFromDoc($doc)
                ?: "Use {$name} as part of the plugin runtime where this {$type} is loaded.",
            'properties' => [],
            'methods' => [],
        ];
    }

    /**
     * @param array<int, mixed> $tokens
     * @return array<string, mixed>|null
     */
    private function readMethod(array $tokens, int $index, ?string $doc): ?array
    {
        $name = '';
        $line = is_array($tokens[$index]) ? $tokens[$index][2] : 0;

        for ($i = $index + 1, $max = count($tokens); $i < $max; $i++) {
            if (is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            if (!is_array($tokens[$i]) && $tokens[$i] === '(') {
                return null;
            }

            if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                $name = $tokens[$i][1];
                break;
            }
        }

        if ($name === '') {
            return null;
        }

        $visibility = $this->visibilityBefore($tokens, $index);
        $signature = trim('function ' . $name . $this->collectUntil($tokens, $i + 1, ['{', ';']));

        return [
            'name' => $name,
            'line' => $line,
            'visibility' => $visibility,
            'static' => $this->hasTokenBefore($tokens, $index, T_STATIC),
            'signature' => $signature,
            'technicalDescription' => $this->descriptionFromDoc($doc) ?: "Method {$name}.",
            'usageDescription' => $this->usageFromDoc($doc) ?: "Call {$name} when this class needs that behavior.",
        ];
    }

    /**
     * @param array<int, mixed> $tokens
     * @return array<string, mixed>
     */
    private function readProperty(array $tokens, int $index, ?string $doc): array
    {
        $name = substr($tokens[$index][1], 1);

        return [
            'name' => $name,
            'line' => $tokens[$index][2],
            'visibility' => $this->visibilityBefore($tokens, $index),
            'static' => $this->hasTokenBefore($tokens, $index, T_STATIC),
            'technicalDescription' => $this->descriptionFromDoc($doc) ?: "Property {$name}.",
            'usageDescription' => $this->usageFromDoc($doc) ?: "Use {$name} as class state.",
        ];
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function collectUntil(array $tokens, int $start, string|array $until): string
    {
        $until = (array) $until;
        $value = '';

        for ($i = $start, $max = count($tokens); $i < $max; $i++) {
            $token = $tokens[$i];

            if (!is_array($token) && in_array($token, $until, true)) {
                break;
            }

            $value .= is_array($token) ? $token[1] : $token;
        }

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function previousMeaningfulToken(array $tokens, int $index): ?int
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            $token = $tokens[$i];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return is_array($token) ? $token[0] : null;
        }

        return null;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function visibilityBefore(array $tokens, int $index): string
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            $token = $tokens[$i];

            if (!is_array($token) && in_array($token, [';', '{', '}'], true)) {
                break;
            }

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            if (!is_array($token)) {
                continue;
            }

            return match ($token[0]) {
                T_PRIVATE => 'private',
                T_PROTECTED => 'protected',
                T_PUBLIC => 'public',
                default => '',
            } ?: 'public';
        }

        return 'public';
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function hasTokenBefore(array $tokens, int $index, int $tokenType): bool
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            $token = $tokens[$i];

            if (!is_array($token) && in_array($token, [';', '{', '}', '(', ',', ')'], true)) {
                return false;
            }

            if (is_array($token) && $token[0] === $tokenType) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function isPropertyToken(array $tokens, int $index): bool
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            $token = $tokens[$i];

            if (!is_array($token) && in_array($token, [';', '{', '}'], true)) {
                return false;
            }

            if (is_array($token) && in_array($token[0], [T_PUBLIC, T_PROTECTED, T_PRIVATE], true)) {
                return true;
            }

            if (is_array($token) && in_array($token[0], [T_FUNCTION, T_FN], true)) {
                return false;
            }
        }

        return false;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function isStringInterpolationClose(array $tokens, int $index): bool
    {
        $sawVariable = false;

        for ($i = $index - 1; $i >= 0; $i--) {
            $token = $tokens[$i];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_STRING_VARNAME], true)) {
                continue;
            }

            if (is_array($token) && $token[0] === T_VARIABLE) {
                $sawVariable = true;
                continue;
            }

            return $sawVariable
                && is_array($token)
                && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true);
        }

        return false;
    }

    private function descriptionFromDoc(?string $doc): ?string
    {
        if ($doc === null) {
            return null;
        }

        $lines = $this->cleanDocLines($doc);

        foreach ($lines as $line) {
            if ($line !== '' && !str_starts_with($line, '@')) {
                return $line;
            }
        }

        return null;
    }

    private function usageFromDoc(?string $doc): ?string
    {
        if ($doc === null) {
            return null;
        }

        foreach ($this->cleanDocLines($doc) as $line) {
            if (str_starts_with($line, '@usage')) {
                return trim(substr($line, 6));
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function cleanDocLines(string $doc): array
    {
        $doc = preg_replace('/^\/\*\*|\*\/$/', '', trim($doc)) ?? $doc;
        $lines = [];

        foreach (explode("\n", $doc) as $line) {
            $line = trim(preg_replace('/^\s*\*\s?/', '', $line) ?? $line);
            $lines[] = $line;
        }

        return $lines;
    }
}
