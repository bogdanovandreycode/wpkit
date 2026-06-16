<?php

declare(strict_types=1);

namespace Wpkit\Controller;

final class GitStateController
{
    /**
     * @return array<int, array{status: string, path: string}>
     */
    public function status(): array
    {
        $output = $this->run('git status --short');
        $entries = [];

        foreach (explode("\n", trim($output)) as $line) {
            if ($line === '') {
                continue;
            }

            preg_match('/^(.{1,2})\s+(.*)$/', $line, $matches);

            $entries[] = [
                'status' => trim($matches[1] ?? substr($line, 0, 2)),
                'path' => trim($matches[2] ?? substr($line, 2)),
            ];
        }

        return $entries;
    }

    public function diff(string $path = '.', bool $staged = false, bool $summary = false): string
    {
        $resolved = WorkspacePath::resolve($path, mustExist: false);
        $relative = WorkspacePath::relative($resolved);
        $command = 'git diff';

        if ($staged) {
            $command .= ' --cached';
        }

        if ($summary) {
            $command .= ' --stat';
        }

        if ($relative !== '.') {
            $command .= ' -- ' . escapeshellarg($relative);
        }

        return $this->run($command);
    }

    private function run(string $command): string
    {
        $root = WorkspacePath::root();
        $safeRoot = str_replace('\\', '/', $root);
        $command = 'git -c safe.directory=' . escapeshellarg($safeRoot) . ' ' . substr($command, 4);

        return shell_exec($command . ' 2>&1') ?? '';
    }
}
