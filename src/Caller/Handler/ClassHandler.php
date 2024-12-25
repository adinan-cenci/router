<?php

namespace AdinanCenci\Router\Caller\Handler;

use AdinanCenci\Router\Instantiator\InstantiatorInterface;
use AdinanCenci\Router\Caller\Exception\CallbackException;

class ClassHandler implements HandlerInterface
{
    /**
     * @var AdinanCenci\Router\Instantiator\InstantiatorInterface
     *   An object capable of instantiating other objects.
     */
    protected InstantiatorInterface $instantiator;

    /**
     * @param AdinanCenci\Router\Instantiator\InstantiatorInterface $instantiator
     *   An object capable of instantiating other objects.
     */
    public function __construct(InstantiatorInterface $instantiator)
    {
        $this->instantiator = $instantiator;
    }

    /**
     * {@inherit}
     */
    public function applies(mixed $value): bool
    {
        return is_string($value) && class_exists($value);
    }

    /**
     * {@inherit}
     */
    public function handle(mixed $callback, array $parameters = []): mixed
    {
        $object = $this->attemptToInstantiate($callback);

        $refObject = new \ReflectionObject($object);
        if (! $refObject->hasMethod('__invoke')) {
            throw new CallbackException(get_class($callback) . ' does not implement ::__invoke()');
        }

        return call_user_func_array($object, $parameters);
    }

    /**
     * Attempts to instantiate a class.
     *
     * @param string $className
     *   The name of the class.
     *
     * @return Object
     *   The instantiated class.
     *
     * @throws AdinanCenci\Router\Caller\Exception\CallbackException
     */
    protected function attemptToInstantiate(string $className)
    {
        try {
            $object = $this->instantiator->instantiate($className);
        } catch (\InvalidArgumentException $e) {
            throw new CallbackException($e->getMessage());
        }

        return $object;
    }
}
