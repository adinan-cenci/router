<?php

namespace AdinanCenci\Router\Caller\Handler;

use AdinanCenci\Router\Caller\Exception\CallbackException;

class StaticMethodHandler implements HandlerInterface
{
    /**
     * {@inherit}
     */
    public function applies(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        list($class, $method) = explode('::', $value) + [null, null];

        if (! class_exists($class)) {
            return false;
        }

        if (! $method) {
            return false;
        }

        $refClass = new \ReflectionClass($class);

        return $refClass->hasMethod($method)
            ? $refClass->getMethod($method)->isStatic()
            : false;
    }

    /**
     * {@inherit}
     */
    public function handle(mixed $callback, array $parameters = []): mixed
    {
        list($class, $method) = explode('::', $callback);

        $refMethod = new \ReflectionMethod($class, $method);

        if (! $refMethod->isPublic()) {
            throw new CallbackException($class . '::' . $methodName . ' is not public');
        }

        return call_user_func_array($callback, $parameters);
    }
}
