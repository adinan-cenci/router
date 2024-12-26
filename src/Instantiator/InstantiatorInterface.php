<?php

namespace AdinanCenci\Router\Instantiator;

/**
 * An object that can instantiate other objects.
 */
interface InstantiatorInterface
{
    /**
     * Intantiate a new object and returns it.
     *
     * @param string $className
     *   The class to be instantiated.
     *
     * @return object
     *   The new object.
     *
     * @throws AdinanCenci\Router\Exception\CallbackException
     */
    public function instantiate(string $className): object;
}
