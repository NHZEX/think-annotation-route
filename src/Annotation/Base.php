<?php

declare(strict_types=1);

namespace Zxin\Think\Route\Annotation;

use BadMethodCallException;

abstract class Base
{
    public static function __set_state(array $an_array): object
    {
        /** @phpstan-ignore-next-line self 也不能避免参数被改变 */
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
    public function __set($name, mixed $value)
    {
        throw new BadMethodCallException(
            sprintf("Unknown property '%s' on annotation '%s'.", $name, static::class)
        );
    }
}
