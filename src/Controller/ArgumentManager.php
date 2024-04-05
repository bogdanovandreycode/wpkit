<?php

namespace Wpkit\Controller;

use Wpkit\Model\ArgumentModel;

class ArgumentManager
{
    /**
     * @param ArgumentModel[] $arguments
     */
    public static function getValueByName(array $arguments, string $name): mixed
    {
        foreach ($arguments as $argument) {
            if ($argument->name == $name) {
                return $argument->value;
            }
        }

        return null;
    }

    public static function getObjectByName(array $arguments, string $name): ?ArgumentModel
    {
        foreach ($arguments as $argument) {
            if ($argument->name === $name) {
                return $argument;
            }
        }

        return null;
    }
}
