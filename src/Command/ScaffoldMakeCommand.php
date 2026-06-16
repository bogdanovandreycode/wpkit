<?php

declare(strict_types=1);

namespace Wpkit\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Wpkit\Controller\ProjectContextResolver;
use Wpkit\Controller\ScaffoldGenerator;
use Wpkit\Model\ScaffoldDefinition;
use Wpkit\Model\ScaffoldFieldModel;

class ScaffoldMakeCommand extends Command
{
    public function __construct(
        private readonly ScaffoldDefinition $definition
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName($this->definition->commandName)
            ->setDescription($this->definition->description)
            ->addArgument('name', InputArgument::OPTIONAL, 'Class name to generate.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $className = (string) $input->getArgument('name');
        if ($className === '') {
            $className = (string) $helper->ask(
                $input,
                $output,
                new Question("Class name (e.g., {$this->definition->classExample}):\n")
            );
        }

        if ($className === '') {
            $output->writeln('<error>Class name is required.</error>');

            return Command::FAILURE;
        }

        $variables = [
            'className' => $className,
            'namespace' => $this->askNamespace($helper, $input, $output),
        ];

        foreach ($this->definition->fields as $field) {
            $variables[$field->name] = $this->askFieldValue($field, $variables, $helper, $input, $output);
        }

        try {
            $targetPath = (new ScaffoldGenerator())->generate($this->definition, $variables);
            $output->writeln("<info>{$this->definition->commandName} created: {$targetPath}</info>");

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $output->writeln("<error>Error: {$exception->getMessage()}</error>");

            return Command::FAILURE;
        }
    }

    private function askNamespace(
        QuestionHelper $helper,
        InputInterface $input,
        OutputInterface $output
    ): string {
        $defaultNamespace = ProjectContextResolver::buildDefaultNamespace(
            $this->definition->namespaceSuffix
        );

        return (string) $helper->ask(
            $input,
            $output,
            new Question(
                "Namespace (e.g., {$defaultNamespace}) [{$defaultNamespace}]:\n",
                $defaultNamespace
            )
        );
    }

    /**
     * @param array<string, string> $variables
     */
    private function askFieldValue(
        ScaffoldFieldModel $field,
        array $variables,
        QuestionHelper $helper,
        InputInterface $input,
        OutputInterface $output
    ): string {
        $defaultValue = $field->default;
        if (is_callable($defaultValue)) {
            $defaultValue = $defaultValue(getcwd(), $variables);
        }

        $displayDefault = $defaultValue === null ? '' : (string) $defaultValue;
        $questionText = $field->prompt;
        $questionText .= $displayDefault === '' ? '' : " [{$displayDefault}]";
        $questionText .= ":\n";

        while (true) {
            $result = $helper->ask(
                $input,
                $output,
                new Question($questionText, $displayDefault === '' ? null : $displayDefault)
            );

            $rawValue = trim((string) ($result ?? ''));

            if ($rawValue === '' && $field->required) {
                continue;
            }

            try {
                return $this->normalizeFieldValue($field, $rawValue);
            } catch (\InvalidArgumentException $exception) {
                $output->writeln("<error>{$exception->getMessage()}</error>");
            }
        }
    }

    private function normalizeFieldValue(ScaffoldFieldModel $field, string $value): string
    {
        if (is_callable($field->normalizer)) {
            return (string) ($field->normalizer)($value);
        }

        return match ($field->type) {
            'int' => $this->normalizeInteger($field->name, $value),
            'bool' => $this->normalizeBoolean($field->name, $value),
            'code' => $value,
            default => var_export($value, true),
        };
    }

    private function normalizeInteger(string $fieldName, string $value): string
    {
        if (!preg_match('/^-?\d+$/', $value)) {
            throw new \InvalidArgumentException("{$fieldName} must be an integer.");
        }

        return $value;
    }

    private function normalizeBoolean(string $fieldName, string $value): string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            '1', 'true', 'yes', 'y' => 'true',
            '0', 'false', 'no', 'n' => 'false',
            default => throw new \InvalidArgumentException("{$fieldName} must be a boolean value."),
        };
    }
}
