<?php

namespace Zxin\Think\Route\Annotation;

use BadMethodCallException;
use function sprintf;

abstract class Base
{
    public function getOptions(): array
    {
        $result = [];
        foreach (
            [
                'middleware',
                'ext',
                'deny_ext',
                'https',
                'domain',
                'complete_match',
                'cache',
                'ajax',
                'pjax',
                'json',
                'filter',
                'append',
            ] as $name
        ) {
            if (!isset($this->$name)) {
                continue;
            }
            $result[] = $this->$name;
        }
        return $result;
    }

    public static function __set_state(array $an_array): object
    {
        return new static(...$an_array);
    }

    /**
     * Error handler for unknown property accessor in Annotation class.
     *
     * @param string $name Unknown property name.
     *
     * @throws BadMethodCallException
     */
    public function __get($name)
    {
        throw new BadMethodCallException(
            sprintf("Unknown property '%s' on annotation '%s'.", $name, static::class)
        );
    }

    /**
     * Error handler for unknown property mutator in Annotation class.
     *
     * @param string $name  Unknown property name.
     * @param mixed  $value Property value.
     *
     * @throws BadMethodCallException
     */
    public function __set($name, $value)
    {
        throw new BadMethodCallException(
            sprintf("Unknown property '%s' on annotation '%s'.", $name, static::class)
        );
    }
}
