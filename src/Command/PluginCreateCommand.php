<?php

declare(strict_types=1);

namespace Wpkit\Command;

use Exception;
use Wpkit\Model\ArgumentModel;
use Wpkit\Controller\PluginMetadata;
use Wpkit\Controller\PluginGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Wpkit\Controller\ArgumentManager;

class PluginCreateCommand extends Command
{
    /**
     * @var ArgumentModel[] array
     */
    private array $arguments;

    /**
     * Build the list of plugin configuration arguments.
     *
     * @return void
     */
    private function buildArguments(): void
    {
        $this->arguments = [
            new ArgumentModel(
                'pluginName',
                "Plugin Name. The display name shown in the WordPress admin panel",
                true
            ),
            new ArgumentModel(
                'pluginSlug',
                "Plugin Slug. Directory name, main plugin file name and package slug",
            ),
            new ArgumentModel(
                'vendor',
                "Vendor. Composer vendor name for package notation vendor/plugin-slug",
                true
            ),
            new ArgumentModel(
                'author',
                "Author. The name of the plugin\'s author",
                true
            ),
            new ArgumentModel(
                'authorURI',
                "Author URI. Link to the author\'s website",
            ),
            new ArgumentModel(
                'namespace',
                "Namespace. Root PHP namespace for plugin classes",
            ),
            new ArgumentModel(
                'description',
                "Description. A brief description of what the plugin does"
            ),
            new ArgumentModel(
                'pluginURI',
                "Plugin URI. A link to the plugin page where users can find additional information about the plugin"
            ),
            new ArgumentModel(
                'version',
                "Version. The current version of the plugin",
                false,
                '1.0.0'
            ),
            new ArgumentModel(
                'phpVersion',
                "PHP version. Minimum PHP version required to run the plugin",
                false,
                '8.1'
            ),
            new ArgumentModel(
                'license',
                "License. The type of plugin license, most often GPL2",
                false,
                'GPL-2.0-or-later'
            ),
            new ArgumentModel(
                'licenseURI',
                "License URI. Link to the website of license",
                false,
                'https://www.gnu.org/licenses/gpl-2.0.html'
            ),
            new ArgumentModel(
                'textDomain',
                "Text Domain. The ID of the text domain for localization of the plugin",
                false,
                ''
            ),
            new ArgumentModel(
                'domainPath',
                "Domain Path. The path to the folder with translations",
                false,
                '/languages'
            ),
        ];
    }

    /**
     * Configure the command options and arguments.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('plugin:create');
        $this->setDescription('Creates a new WordPress plugin structure.');
        $this->setHelp('This command allows you to create a new WordPress plugin.');
        $this->addOption(
            'install',
            'i',
            InputOption::VALUE_NONE,
            'Run composer install inside the generated plugin after scaffolding.'
        );
        $this->buildArguments();
    }

    /**
     * Execute the plugin creation command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $index = 0;
        $argumentLength = count($this->arguments);

        while ($index < $argumentLength) {
            $argument = $this->arguments[$index];

            $this->refreshDerivedDefaults();

            $questionText = $argument->required ? '*' : '';
            $questionText .= empty($argument->description) ? "Argument {$argument->value}" : $argument->description;
            $questionText .= empty($argument->value) ? '' : " [{$argument->value}]";
            $questionText .= ":\n";
            $question = new Question($questionText);
            $result = $helper->ask($input, $output, $question);

            if (empty($result) && $argument->required) {
                continue;
            }

            $argument->value = empty($result) ? $argument->value : $result;
            $index++;
        }

        try {
            $generator = new PluginGenerator($this->arguments, (bool) $input->getOption('install'));
            $generator->generate();
            $output->writeln("Plugin is created.");

            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln("Error: " . $e->getMessage());

            return Command::FAILURE;
        }
    }

    private function refreshDerivedDefaults(): void
    {
        $pluginName = PluginMetadata::normalizePluginName(
            (string) ArgumentManager::getValueByName($this->arguments, 'pluginName')
        );

        if ($pluginName === '') {
            return;
        }

        $pluginSlug = PluginMetadata::slugify($pluginName);
        $namespace = PluginMetadata::namespaceify($pluginName);

        $slugArgument = ArgumentManager::getObjectByName($this->arguments, 'pluginSlug');
        if ($slugArgument !== null && empty($slugArgument->value)) {
            $slugArgument->value = $pluginSlug;
        }

        $namespaceArgument = ArgumentManager::getObjectByName($this->arguments, 'namespace');
        if ($namespaceArgument !== null && empty($namespaceArgument->value)) {
            $namespaceArgument->value = $namespace;
        }

        $textDomainArgument = ArgumentManager::getObjectByName($this->arguments, 'textDomain');
        if ($textDomainArgument !== null && empty($textDomainArgument->value)) {
            $textDomainArgument->value = $pluginSlug;
        }
    }
}
