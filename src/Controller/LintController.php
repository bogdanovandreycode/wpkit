<?php

namespace Wpkit\Controller;

use RecursiveDirectoryIterator;
use RecursiveCallbackFilterIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

class LintController
{
    /**
     * Check PHP syntax for all PHP files in the given directory.
     *
     * @param string $root
     * @param array $exclude
     * @return bool
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
            echo "No PHP files found.\n";

            return true;
        }

        echo "Checking " . count($files) . " files…\n";

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
            echo "\n❌ Syntax errors detected.\n";

            return false;
        }

        echo "✅ No syntax errors detected.\n";

        return true;
    }
}
