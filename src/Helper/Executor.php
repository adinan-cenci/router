<?php

namespace AdinanCenci\Router\Helper;

use AdinanCenci\Router\Exception\CallbackException;

/**
 * Executes different kinds of callbacks.
 */
class Executor
{
    /**
     * @var mixed
     *   The callback: An anonymous function, the name of a function, the
     *   method of a class, an object and its method, an instance of
     *   Psr\Http\Server\MiddlewareInterface or even the path to a file.
     */
    protected $callback;

    /**
     * @var array
     *   The parameters to be passed to the callback.
     */
    protected array $parameters;

    /**
     * @param mixed $callback
     *   The callback.
     * @param array $parameters
     *   The parameters to be passed to the callback.
     */
    public function __construct($callback, array $parameters)
    {
        if ($this->isArrayOfStrings($callback)) {
            $callback = implode('::', $callback);
        }

        $this->callback   = $callback;
        $this->parameters = $parameters;
    }

    /**
     * Executes the callback.
     */
    public function callIt()
    {
        if ($this->isFile($this->callback)) {
            return $this->includeFile();
        }

        if ($this->isClass($this->callback)) {
            return $this->instantiateAndInvoke();
        }

        if ($this->isStaticMethod($this->callback)) {
            return $this->callStaticMethod();
        }

        if ($this->isMethod($this->callback)) {
            return $this->instantiateAndCallMethod();
        }

        if ($this->isFunction($this->callback)) {
            return $this->callFunction();
        }

        if (is_object($this->callback)) {
            return $this->invoke();
        }

        if ($this->isObjectAndMethod($this->callback)) {
            return $this->callMethod();
        }

        $message = 'Could not execute the callback, it is either undefined, does not exist or it is invalid.';
        throw new CallbackException($message);
    }

    /**
     * Checks if $value is a file.
     *
     * @param mixed $value
     *   The value to check.
     *
     * @return bool
     *   True if it is a file.
     */
    protected function isFile($value): bool
    {
        return is_string($value) && file_exists($value) && is_file($value);
    }

    /**
     * Include the callback file.
     */
    protected function includeFile()
    {
        extract($this->parameters);
        return include($this->callback);
    }

    /**
     * Checks if $value is the name of a class.
     *
     * @param mixed $value
     *   The value to check.
     *
     * @return bool
     *   True if it is a class.
     */
    protected function isClass($value): bool
    {
        return is_string($value) && class_exists($value);
    }

    protected function instantiateAndInvoke()
    {
        $object = $this->attemptToInstantiate($this->callback);
        return call_user_func_array($object, $this->parameters);
    }

    /**
     * Checks if $value is the name of a class and its static method.
     *
     * @param mixed $value
     *   The value to check.
     *
     * @return bool
     *   True if it is the name of a class and its static method.
     */
    protected function isStaticMethod($value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        list($class, $method) = explode('::', $value) + [null, null];

        if (! class_exists($class)) {
            return false;
        }

        $refClass = new \ReflectionClass($class);

        return $refClass->hasMethod($method)
            ? $refClass->getMethod($method)->isStatic()
            : false;
    }

    /**
     * Calls the static callback method.
     */
    protected function callStaticMethod()
    {
        list($class, $method) = explode('::', $this->callback);

        $refMethod = new \ReflectionMethod($class, $method);

        if (! $refMethod->isPublic()) {
            throw new CallbackException($methodName . ' is not public');
        }

        return call_user_func_array($this->callback, $this->parameters);
    }

    /**
     * Checks if $value is the name of a class and its method.
     *
     * @param mixed $value
     *   The value to check.
     *
     * @return bool
     *   True if it is the name of a class and its method.
     */
    protected function isMethod($value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        list($class, $method) = explode('::', $value) + [null, null];

        if (! class_exists($class)) {
            return false;
        }

        $refClass = new \ReflectionClass($class);

        return $refClass->hasMethod($method)
            ? !$refClass->getMethod($method)->isStatic()
            : false;
    }

    /**
     * Instantiates the class and calls the callback method.
     */
    protected function instantiateAndCallMethod()
    {
        list($class, $method) = explode('::', $this->callback);

        $object = $this->attemptToInstantiate($class);

        $reflMethod = new \ReflectionMethod($object, $method);

        if (! $reflMethod->isPublic()) {
            throw new CallbackException($this->callback . ' is not public');
        }

        return call_user_func_array([$object, $method], $this->parameters);
    }

    /**
     * Checks if $value is a function.
     *
     * @param mixed $value
     *   The value to check.
     *
     * @return bool
     *   True if it is a function.
     */
    protected function isFunction($value): bool
    {
        return is_string($value) && function_exists($value);
    }

    /**
     * Calls the callback function.
     */
    protected function callFunction()
    {
        return call_user_func_array($this->callback, $this->parameters);
    }

    /**
     * Checks if $value is an object and a method of said object.
     *
     * @param mixed $value
     *   The value to check.
     *
     * @return bool
     *   True if it is an object and a method.
     */
    protected function isObjectAndMethod($value): bool
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
     * Calls the callback method of the object.
     */
    protected function callMethod()
    {
        list($object, $method) = $this->callback;

        $reflMethod = new \ReflectionMethod($object, $method);

        if (! $reflMethod->isPublic()) {
            throw new CallbackException(get_class($object) . '::' . $method . ' is not public');
        }

        return call_user_func_array([$object, $method], $this->parameters);
    }

    /**
     * Calls the object/function callback.
     */
    protected function invoke()
    {
        return call_user_func_array($this->callback, $this->parameters);
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
     * @throws AdinanCenci\Router\Exception\CallbackException
     */
    protected function attemptToInstantiate(string $className)
    {
        $refClass = new \ReflectionClass($className);

        if ($refClass->isAbstract()) {
            throw new CallbackException($className . ' is abstract');
        }

        $refClass = new \ReflectionClass($className);
        $rfConstructor = $refClass->getConstructor();

        if ($rfConstructor && $rfConstructor->getNumberOfParameters() > 0) {
            throw new CallbackException($className . ': Class has dependencies that could not be resolved');
        }

        return new $className();
    }

    /**
     * Checks if $value is an arra of string only.
     *
     * @param array $value
     *   The value to check.
     *
     * @return bool
     *   True it is an array of strings.
     */
    protected function isArrayOfStrings($value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        $filtered = array_filter($value, 'is_string');
        return count($value) == count($filtered);
    }
}
