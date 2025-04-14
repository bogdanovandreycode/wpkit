<?php

namespace Wpkit\Command;

use Exception;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;

class RouteCreateCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('route:create')
            ->setDescription('Create REST API RouteController class.')
            ->setHelp('This command generates a new RouteController file based on template or YAML config.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        // Сначала спросим, хочет ли он сгенерировать вручную или по YAML
        $question = new Question("Enter path to YAML file (or leave empty for manual):\n");
        $path = $helper->ask($input, $output, $question);

        $routes = [];

        if ($path && file_exists($path)) {
            $routes = Yaml::parseFile($path);
        } else {
            $question = new Question("Class name (e.g., SendEmailConfirmRoute):\n");
            $className = $helper->ask($input, $output, $question);

            $question = new Question("Namespace (e.g., MyPlugin\\Controller\\Route):\n");
            $namespace = $helper->ask($input, $output, $question);

            $question = new Question("Route namespace (e.g., my-plugin/v1):\n");
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

    private function generateRouteController(array $data, OutputInterface $output): void
    {
        $templatePath = __DIR__ . '/../Template/route.template';
        $projectRoot = getcwd(); // путь где ты запускаешь wpkit, то есть твой плагин
        $relativePath = 'src/' . str_replace('\\', '/', $data['namespace']);
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
}
