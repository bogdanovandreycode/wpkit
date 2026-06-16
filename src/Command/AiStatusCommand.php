<?php

declare(strict_types=1);

namespace Wpkit\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wpkit\Controller\GitStateController;

final class AiStatusCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('ai:status')
            ->setDescription('Show git working tree status.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $status = (new GitStateController())->status();

        if ($input->getOption('json')) {
            $output->writeln(json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            foreach ($status as $entry) {
                $output->writeln("{$entry['status']} {$entry['path']}");
            }
        }

        return Command::SUCCESS;
    }
}
