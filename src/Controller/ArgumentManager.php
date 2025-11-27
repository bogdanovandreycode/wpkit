<?php

namespace Wpkit\Controller;

use Wpkit\Model\ArgumentModel;

class ArgumentManager
{
    /**
     * Get the value of an argument by its name.
     *
     * @param ArgumentModel[] $arguments
     * @param string $name
     * @return mixed
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

    /**
     * Get an ArgumentModel object by its name.
     *
     * @param ArgumentModel[] $arguments
     * @param string $name
     * @return ArgumentModel|null
     */
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
