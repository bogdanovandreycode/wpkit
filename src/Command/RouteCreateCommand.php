<?php

declare(strict_types=1);

namespace Wpkit\Command;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;

class RouteCreateCommand extends Command
{
    /**
     * Configure the command options and arguments.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('route:create')
            ->setDescription('Create REST API RouteController class.')
            ->setHelp('This command generates a new RouteController file based on template or YAML config.');
    }

    /**
     * Execute the route creation command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $baseNamespace = $this->detectBaseNamespace();
        $defaultNamespace = $baseNamespace === null
            ? 'Plugin\\Http\\Routes'
            : $baseNamespace . '\\Http\\Routes';
        $defaultRouteNamespace = basename(getcwd()) . '/v1';

        $question = new Question("Enter path to YAML file (or leave empty for manual):\n");
        $path = $helper->ask($input, $output, $question);

        $routes = [];

        if ($path && file_exists($path)) {
            $routes = Yaml::parseFile($path);
        } else {
            $question = new Question("Class name (e.g., SendEmailConfirmRoute):\n");
            $className = $helper->ask($input, $output, $question);

            $question = new Question("Namespace (e.g., {$defaultNamespace}) [{$defaultNamespace}]:\n", $defaultNamespace);
            $namespace = $helper->ask($input, $output, $question);

            $question = new Question("Route namespace (e.g., {$defaultRouteNamespace}) [{$defaultRouteNamespace}]:\n", $defaultRouteNamespace);
            $routeNamespace = $helper->ask($input, $output, $question);

            $question = new Question("Route path (e.g., /sendEmailConfirmCode):\n");
            $routePath = $helper->ask($input, $output, $question);

            $routes[] = compact('className', 'namespace', 'routeNamespace', 'routePath');
        }

        foreach ($routes as $route) {
            $this->generateRouteController($route, $output);
        }

        return Command::SUCCESS;
    }

    /**
     * Generate a RouteController class file from template.
     *
     * @param array $data
     * @param OutputInterface $output
     * @return void
     */
    private function generateRouteController(array $data, OutputInterface $output): void
    {
        $templatePath = __DIR__ . '/../Template/route.template';
        $projectRoot = getcwd();
        $baseNamespace = $this->detectBaseNamespace();
        $relativeNamespace = $data['namespace'];

        if ($baseNamespace !== null && str_starts_with($relativeNamespace, $baseNamespace . '\\')) {
            $relativeNamespace = substr($relativeNamespace, strlen($baseNamespace) + 1);
        }

        $relativePath = 'src/' . str_replace('\\', '/', $relativeNamespace);
        $targetPath = $projectRoot . '/' . $relativePath . '/' . $data['className'] . '.php';

        if (!file_exists($templatePath)) {
            $output->writeln("<error>Template file not found: {$templatePath}</error>");

            return;
        }

        $template = file_get_contents($templatePath);

        $searchReplace = [
            '{{namespace}}' => $data['namespace'],
            '{{className}}' => $data['className'],
            '{{routeNamespace}}' => $data['routeNamespace'],
            '{{routePath}}' => $data['routePath'],
        ];

        $result = str_replace(array_keys($searchReplace), array_values($searchReplace), $template);

        $dir = dirname($targetPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($targetPath, $result);

        $output->writeln("<info>RouteController {$data['className']} created at {$targetPath}</info>");
    }

    private function detectBaseNamespace(): ?string
    {
        $composerJson = getcwd() . '/composer.json';

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

        $namespace = (string) array_key_first($psr4);

        return rtrim($namespace, '\\');
    }
}
