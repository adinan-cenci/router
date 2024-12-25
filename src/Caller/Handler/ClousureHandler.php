<?php

namespace AdinanCenci\Router\Caller\Handler;

class ClousureHandler implements HandlerInterface
{
    /**
     * {@inherit}
     */
    public function applies(mixed $value): bool
    {
        return $value instanceof \Closure;
    }

    /**
     * {@inherit}
     */
    public function handle(mixed $callback, array $parameters = []): mixed
    {
        return call_user_func_array($callback, $parameters);
    }
}
