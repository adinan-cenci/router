<?php

namespace AdinanCenci\Router\Caller\Handler;

class FileHandler implements HandlerInterface
{
    /**
     * {@inherit}
     */
    public function applies(mixed $value): bool
    {
        return is_string($value) && file_exists($value) && is_file($value);
    }

    /**
     * {@inherit}
     */
    public function handle(mixed $callback, array $parameters = []): mixed
    {
        extract($parameters);
        return include($callback);
    }
}
