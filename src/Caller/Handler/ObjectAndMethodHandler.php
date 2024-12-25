<?php

namespace AdinanCenci\Router\Caller\Handler;

use AdinanCenci\Router\Caller\Exception\CallbackException;

class ObjectAndMethodHandler implements HandlerInterface
{
    /**
     * {@inherit}
     */
    public function applies(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        $object = reset($value);
        if (! is_object($object)) {
            return false;
        }

        $method = end($value);

        return is_string($method) && method_exists($object, $method);
    }

    /**
     * {@inherit}
     */
    public function handle(mixed $callback, array $parameters = []): mixed
    {
        list($object, $method) = $callback;

        $reflMethod = new \ReflectionMethod($object, $method);

        if (! $reflMethod->isPublic()) {
            throw new CallbackException(get_class($object) . '::' . $method . ' is not public');
        }

        return call_user_func_array([$object, $method], $parameters);
    }
}
