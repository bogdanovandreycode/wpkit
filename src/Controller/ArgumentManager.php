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

    /**
     * Set the value of an argument by its name or create it if it does not exist.
     *
     * @param ArgumentModel[] $arguments
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public static function setValueByName(array &$arguments, string $name, mixed $value): void
    {
        $argument = self::getObjectByName($arguments, $name);

        if ($argument !== null) {
            $argument->value = $value;

            return;
        }

        $arguments[] = new ArgumentModel($name, '', false, $value);
    }
}
