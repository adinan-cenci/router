<?php

namespace AdinanCenci\Router\Caller\Handler;

class FunctionHandler extends ClousureHandler implements HandlerInterface
{
    /**
     * {@inherit}
     */
    public function applies(mixed $value): bool
    {
        return is_string($value) && function_exists($value);
    }
}
