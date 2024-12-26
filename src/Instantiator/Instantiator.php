<?php

namespace AdinanCenci\Router\Instantiator;

/**
 * Default instantiator.
 */
class Instantiator implements InstantiatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function instantiate(string $className): object
    {
        $refClass = new \ReflectionClass($className);

        if ($refClass->isAbstract()) {
            throw new \InvalidArgumentException($className . ' is abstract');
        }

        $refClass = new \ReflectionClass($className);
        $rfConstructor = $refClass->getConstructor();

        if ($rfConstructor && $rfConstructor->getNumberOfParameters() > 0) {
            throw new \InvalidArgumentException($className . ': Class has dependencies that could not be resolved');
        }

        return new $className();
    }
}
