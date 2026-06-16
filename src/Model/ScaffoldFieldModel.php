<?php

declare(strict_types=1);

namespace Wpkit\Model;

class ScaffoldFieldModel
{
    public function __construct(
        public string $name,
        public string $prompt,
        public string $type = 'string',
        public bool $required = false,
        public mixed $default = null,
        public mixed $normalizer = null,
    ) {
    }
}
