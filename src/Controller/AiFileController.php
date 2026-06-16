<?php

declare(strict_types=1);

namespace Wpkit\Controller;

use InvalidArgumentException;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

final class AiFileController
{
    private const DEFAULT_EXCLUDES = [
        '.git',
        'vendor',
        'node_modules',
        'build',
        'dist',
        '.tmp-scaffold-test',
        '.tmp-admin-post-test',
    ];

    /**
     * @return array{path: string, start: int, end: int, totalLines: int, content: string}
     */
    public function read(string $path, int $start = 1, ?int $lines = null): array
    {
        $file = WorkspacePath::resolve($path, mustExist: true);

        if (!is_file($file)) {
            throw new InvalidArgumentException("Path is not a file: {$path}");
        }

        $allLines = file($file, FILE_IGNORE_NEW_LINES);
        if ($allLines === false) {
            throw new InvalidArgumentException("Unable to read file: {$path}");
        }

        $total = count($allLines);
        $start = max(1, $start);
        $length = $lines === null ? null : max(0, $lines);
        $slice = array_slice($allLines, $start - 1, $length, true);
        $end = $slice === [] ? $start - 1 : (int) array_key_last($slice) + 1;

        return [
            'path' => WorkspacePath::relative($file),
            'start' => $start,
            'end' => $end,
            'totalLines' => $total,
            'content' => implode(PHP_EOL, $slice),
        ];
    }

    /**
     * @return array{path: string, bytes: int, mode: string}
     */
    public function write(
        string $path,
        string $content,
        bool $append = false,
        bool $force = false,
        bool $mkdir = true
    ): array {
        $file = WorkspacePath::resolve($path);

        if (is_dir($file)) {
            throw new InvalidArgumentException("Path is a directory: {$path}");
        }

        if (file_exists($file) && !$append && !$force) {
            throw new InvalidArgumentException("File exists. Use --force to overwrite: {$path}");
        }

        $directory = dirname($file);
        if (!is_dir($directory)) {
            if (!$mkdir) {
                throw new InvalidArgumentException("Directory does not exist: {$directory}");
            }

            mkdir($directory, 0777, true);
        }

        $mode = $append ? FILE_APPEND : 0;
        $bytes = file_put_contents($file, $content, $mode);

        if ($bytes === false) {
            throw new InvalidArgumentException("Unable to write file: {$path}");
        }

        return [
            'path' => WorkspacePath::relative($file),
            'bytes' => $bytes,
            'mode' => $append ? 'append' : 'write',
        ];
    }

    /**
     * @return array<int, array{path: string, line: int|null, preview: string, type: string}>
     */
    public function find(
        string $query,
        string $path = '.',
        bool $nameOnly = false,
        int $maxResults = 100
    ): array {
        $root = WorkspacePath::resolve($path, mustExist: true);
        $results = [];

        foreach ($this->files($root) as $file) {
            $relative = WorkspacePath::relative($file);

            if (stripos(basename($file), $query) !== false) {
                $results[] = [
                    'path' => $relative,
                    'line' => null,
                    'preview' => basename($file),
                    'type' => 'filename',
                ];
            }

            if ($nameOnly || !$this->isTextFile($file)) {
                if (count($results) >= $maxResults) {
                    break;
                }
                continue;
            }

            $lines = file($file, FILE_IGNORE_NEW_LINES);
            if ($lines === false) {
                continue;
            }

            foreach ($lines as $index => $line) {
                if (stripos($line, $query) === false) {
                    continue;
                }

                $results[] = [
                    'path' => $relative,
                    'line' => $index + 1,
                    'preview' => trim($line),
                    'type' => 'content',
                ];

                if (count($results) >= $maxResults) {
                    break 2;
                }
            }
        }

        return array_slice($results, 0, $maxResults);
    }

    /**
     * @return string[]
     */
    private function files(string $root): array
    {
        if (is_file($root)) {
            return [$root];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
                function ($current): bool {
                    return !$current->isDir()
                        || !in_array($current->getFilename(), self::DEFAULT_EXCLUDES, true);
                }
            ),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = str_replace('\\', '/', $file->getPathname());
            }
        }

        sort($files);

        return $files;
    }

    private function isTextFile(string $file): bool
    {
        $chunk = file_get_contents($file, false, null, 0, 512);

        return $chunk !== false && !str_contains($chunk, "\0");
    }
}
