<?php

declare(strict_types=1);

namespace Wpkit\Controller;

use InvalidArgumentException;
use Wpkit\Model\ScaffoldDefinition;

final class ScaffoldGenerator
{
    /**
     * @param array<string, string> $variables
     */
    public function generate(ScaffoldDefinition $definition, array $variables): string
    {
        $className = $variables['className'] ?? null;
        $namespace = $variables['namespace'] ?? null;

        if ($className === null || $namespace === null) {
            throw new InvalidArgumentException('className and namespace are required.');
        }

        $targetPath = ProjectContextResolver::resolveTargetPath($namespace, $className);

        if (file_exists($targetPath)) {
            throw new InvalidArgumentException("Target file already exists: {$targetPath}");
        }

        TemplateEngine::generateFromMap($definition->templateName, $variables, $targetPath);

        return $targetPath;
    }
}
