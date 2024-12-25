<?php

namespace AdinanCenci\Router\Caller\Handler;

interface HandlerInterface
{
    /**
     * Checks if the the handler is able to deal with the specified callback.
     *
     * @param mixed $callback
     *   A closure, a function, the method of a class, an object and its
     *   method, a file etc.
     *
     * @return bool
     *   If it is able to handle the callback or not.
     */
    public function applies(mixed $callback): bool;

    /**
     * Executes the callback.
     *
     * And returns the output.
     *
     * @param mixed $callback
     *   The callback.
     * @param array $parameters
     *   The parameters to be handed over to the callback.
     *
     * @return mixed
     *   The value returned by the callback.
     */
    public function handle(mixed $callback, array $parameters = []): mixed;
}
