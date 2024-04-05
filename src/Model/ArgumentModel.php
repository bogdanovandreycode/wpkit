<?php

namespace Wpkit\Model;

class ArgumentModel
{
    public function __construct(
        public string $name,
        public string $description,
        public bool $required = false,
        public mixed $value = null
    ) {
    }
}
