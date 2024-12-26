<?php

namespace AdinanCenci\Router\Caller\Handler;

use AdinanCenci\Router\Caller\Exception\CallbackException;

class ObjectHandler implements HandlerInterface
{
    /**
     * {@inherit}
     */
    public function applies(mixed $value): bool
    {
        return is_object($value);
    }

    /**
     * {@inherit}
     */
    public function handle(mixed $callback, array $parameters = []): mixed
    {
        $refObject = new \ReflectionObject($callback);

        if (! $refObject->hasMethod('__invoke')) {
            throw new CallbackException('Object ' . get_class($callback) . ' does not implement ::__invoke()');
        }

        return call_user_func_array($callback, $parameters);
    }
}
