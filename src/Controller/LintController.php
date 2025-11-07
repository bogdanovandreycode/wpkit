<?php

namespace Wpkit\Controller;

use RecursiveDirectoryIterator;
use RecursiveCallbackFilterIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

class LintController
{
    /**
     * Проверяет синтаксис всех PHP-файлов рекурсивно.
     *
     * @param string $root  корневая папка (по умолчанию текущая)
     * @param array  $exclude исключаемые директории
     * @return bool true если ошибок нет
     */
    public function check(string $root = '.', array $exclude = []): bool
    {
        $exclude = $exclude ?: ['vendor', 'node_modules', 'storage', '.git', 'dist', 'build'];

        $rii = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
                function ($current, $key, $iterator) use ($exclude) {
                    if ($current->isDir() && in_array($current->getFilename(), $exclude, true)) {
                        return false;
                    }
                    return true;
                }
            ),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $files = [];
        foreach ($rii as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $files[] = $file->getPathname();
            }
        }

        if (!$files) {
            echo "PHP-файлы не найдены.\n";
            return true;
        }

        echo "Проверяю " . count($files) . " файлов…\n";

        $failed = false;
        foreach ($files as $path) {
            $cmd = 'php -l ' . escapeshellarg($path) . ' 2>&1';
            $out = shell_exec($cmd) ?? '';
            if (strpos($out, 'No syntax errors detected') === false) {
                echo $out;
                $failed = true;
            }
        }

        if ($failed) {
            echo "\n❌ Обнаружены синтаксические ошибки.\n";
            return false;
        }

        echo "✅ Синтаксических ошибок не обнаружено.\n";
        return true;
    }
}
