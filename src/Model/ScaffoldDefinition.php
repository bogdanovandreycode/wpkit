<?php

declare(strict_types=1);

namespace Wpkit\Model;

class ScaffoldDefinition
{
    /**
     * @param ScaffoldFieldModel[] $fields
     */
    public function __construct(
        public string $commandName,
        public string $description,
        public string $templateName,
        public string $namespaceSuffix,
        public string $classExample,
        public array $fields = [],
    ) {
    }
}
