<?php

namespace AdinanCenci\Router\Caller;

use AdinanCenci\Router\Caller\Exception\CallbackException;
use AdinanCenci\Router\Caller\Handler\ClassHandler;
use AdinanCenci\Router\Caller\Handler\ClousureHandler;
use AdinanCenci\Router\Caller\Handler\FileHandler;
use AdinanCenci\Router\Caller\Handler\FunctionHandler;
use AdinanCenci\Router\Caller\Handler\MethodHandler;
use AdinanCenci\Router\Caller\Handler\ObjectAndMethodHandler;
use AdinanCenci\Router\Caller\Handler\ObjectHandler;
use AdinanCenci\Router\Caller\Handler\StaticMethodHandler;
use AdinanCenci\Router\Instantiator\InstantiatorInterface;
use AdinanCenci\Router\Instantiator\Instantiator as DefaultInstantiator;

class Caller implements CallerInterface
{
    /**
     * @var AdinanCenci\Router\Caller\Handler\HandlerInterface[]
     *   Array of callback handlers.
     */
    protected array $handlers;

    /**
     * @param AdinanCenci\Router\Caller\Handler\HandlerInterface[]
     *   Array of callback handlers.
     */
    public function __construct(array $handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * {@inheritdoc}
     */
    public function callIt(mixed $callback, array $parameters = []): mixed
    {
        $handlers = $this->getApplicableHandlers($callback, $parameters);
        if (! $handlers) {
            $message = 'Could not execute the callback, it is either undefined, does not exist or it is invalid.';
            throw new CallbackException($message);
        }

        $handler = reset($handlers);

        return $handler->handle($callback, $parameters);
    }

    /**
     * Get handlers that applies for the specified $callback.
     *
     * @param mixed $callback
     *   The callback.
     *
     * @return AdinanCenci\Router\Caller\Handler\HandlerInterface[]
     *   An array of handlers that could deal with the $callback.
     */
    public function getApplicableHandlers(mixed $callback): array
    {
        return array_filter($this->handlers, function ($handler) use ($callback) {
            return $handler->applies($callback);
        });
    }

    /**
     * Returns a caller object munitiated with the built in handlers.
     *
     * @param null|AdinanCenci\Router\Instantiator\InstantiatorInterface $instantiator
     *   An object capable of instantiating other objects.
     *   If none is informed, the default built in implementation will be used.
     *
     * @return AdinanCenci\Router\Caller\CallerInterface
     *   The caller object.
     */
    public static function withDefaultHandlers(?InstantiatorInterface $instantiator = null): CallerInterface
    {
        $instantiator = $instantiator
            ? $instantiator
            : new DefaultInstantiator();

        $handlers = [
            new ClassHandler($instantiator),
            new ClousureHandler(),
            new FileHandler(),
            new FunctionHandler(),
            new MethodHandler($instantiator),
            new ObjectAndMethodHandler(),
            new ObjectHandler(),
            new StaticMethodHandler(),
        ];

        return new self($handlers);
    }
}
