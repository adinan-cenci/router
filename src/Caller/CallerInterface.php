<?php

namespace AdinanCenci\Router\Caller;

/**
 * Executes a piece of code.
 */
interface CallerInterface
{
    /**
     * Executes the callback with a given set of parameters.
     *
     * And returns the output.
     *
     * @param mixed $callback
     *   A closure, a function, the method of a class, an object and its
     *   method, a file etc.
     *
     * @param array $parameters
     *   An array of parameters.
     *
     * @return mixed
     *   Whatever the callback returns.
     */
    public function callIt(mixed $callback, array $parameters = []): mixed;
}
