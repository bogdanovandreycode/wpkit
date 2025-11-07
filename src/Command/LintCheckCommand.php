<?php

namespace Wpkit\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wpkit\Controller\LintController;

class LintCheckCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('lint:check')
            ->setDescription('Recursively checks PHP syntax via php -l.')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Root directory to scan (default: current).',
                '.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        $lint = new LintController();

        $ok = $lint->check($path);

        return $ok ? Command::SUCCESS : Command::FAILURE;
    }
}
