<?php

namespace Wpkit\Command;

use Exception;
use Wpkit\Model\ArgumentModel;
use Wpkit\Controller\PluginGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;

class PluginCreateCommand extends Command
{
    /**
     * @var ArgumentModel[] array
     */
    private array $arguments;

    private function buildArguments(): void
    {
        $this->arguments = [
            new ArgumentModel(
                'pluginName',
                'The name of the plugin, as it will be displayed in the WordPress admin panel.',
                true
            ),
            new ArgumentModel(
                'vendor',
                'The name of the directory where all the libraries and dependencies of your project downloaded via Composer are stored.',
                true
            ),
            new ArgumentModel(
                'namespace',
                'Common namespace for plugin classes.',
                true
            ),
            new ArgumentModel(
                'phpVersion',
                'Minimum PHP version required to run the plugin.',
                '8.0',
                true
            ),
            new ArgumentModel(
                'author',
                'The name of the plugin\'s author.',
                true
            ),
            new ArgumentModel(
                'authorURI',
                'Link to the author\'s website.',
            ),
            new ArgumentModel(
                'description',
                'A brief description of what the plugin does.'
            ),
            new ArgumentModel(
                'pluginURI',
                'A link to the plugin page where users can find additional information about the plugin.'
            ),
            new ArgumentModel(
                'version',
                'The current version of the plugin.',
                false,
                '1.0'
            ),
            new ArgumentModel(
                'license',
                'The type of plugin license, most often GPL2.',
                false,
                'GPL2'
            ),
            new ArgumentModel(
                'licenseURI',
                'Link to the website of license.',
                false,
                'https://www.gnu.org/licenses/gpl-2.0.html'
            ),
            new ArgumentModel(
                'textDomain',
                'The ID of the text domain for localization of the plugin.',
                false,
                'text-domain'
            ),
            new ArgumentModel(
                'domainPath',
                'The path to the folder with translations.',
                false,
                '/languages'
            ),
        ];
    }

    protected function configure(): void
    {
        $this->setName('plugin:create');
        $this->setDescription('Creates a new WordPress plugin structure.');
        $this->setHelp('This command allows you to create a new WordPress plugin.');
        $this->buildArguments();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $index = 0;
        $argumentLength = count($this->arguments);

        while ($index < $argumentLength) {
            $argument = $this->arguments[$index];
            $questionText = empty($argument->description) ?  "Arg {$argument->value}" : $argument->description;
            $questionText .= empty($argument->value) ? '' : "[{$argument->value}]";
            $question = new Question($questionText);
            $result = $helper->ask($input, $output, $question);

            if (empty($result) && $argument->required) {
                continue;
            }

            $index++;
        }

        try {
            $generator = new PluginGenerator($this->arguments);
            $generator->generate();
            $output->writeln("Plugin is created.");
            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln("Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
