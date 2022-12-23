<?php 
namespace AdinanCenci\Router\Helper;

use AdinanCenci\Router\Exception\CallbackException;

class Executor 
{
    protected $callback;

    protected array $parameters;

    public function __construct($callback, array $parameters) 
    {
        if ($this->isArrayOfStrings($callback)) {
            $callback = implode('::', $callback);
        }

        $this->callback   = $callback;
        $this->parameters = $parameters;
    }

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

        throw new CallbackException('Could not execute the callback, it is either undefined or invalid.');
    }

    protected function isFile($string) : bool
    {
        return is_string($string) && file_exists($string) && is_file($string);
    }

    protected function includeFile() 
    {
        extract($this->parameters);
        return include($this->callback);
    }

    protected function isClass($string) : bool
    {
        return is_string($string) && class_exists($string);
    }

    protected function instantiateAndInvoke() 
    {
        $object = $this->attemptToInstantiate($this->callback);
        return call_user_func_array($object, $this->parameters);
    }

    protected function isStaticMethod($string) : bool
    {
        if (! is_string($string)) {
            return false;
        }

        list($class, $method) = explode('::', $string) + [null, null];

        if (! class_exists($class)) {
            return false;
        }

        $refClass = new \ReflectionClass($class);

        return $refClass->hasMethod($method)
            ? $refClass->getMethod($method)->isStatic()
            : false;
    }

    protected function callStaticMethod() 
    {
        list($class, $method) = explode('::', $this->callback);

        $refMethod = new \ReflectionMethod($class, $method);

        if (! $refMethod->isPublic()) {
            throw new CallbackException($methodName . ' is not public');
        }

        return call_user_func_array($this->callback, $this->parameters);
    }

    protected function isMethod($string) : bool
    {
        if (! is_string($string)) {
            return false;
        }

        list($class, $method) = explode('::', $string) + [null, null];

        if (! class_exists($class)) {
            return false;
        }

        $refClass = new \ReflectionClass($class);

        return $refClass->hasMethod($method)
            ? !$refClass->getMethod($method)->isStatic()
            : false;
    }

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

    protected function isFunction($string) : bool 
    {
        return is_string($string) && function_exists($string);
    }

    protected function callFunction() 
    {
        return call_user_func_array($this->callback, $this->parameters);
    }

    protected function isObjectAndMethod($callback) 
    {
        if (! is_array($callback)) {
            return false;
        }

        $object = reset($callback);

        if (! is_object($object)) {
            return false;
        }

        $method = end($callback);

        return is_string($method) && method_exists($object, $method);
    }

    protected function callMethod() 
    {
        list($object, $method) = $this->callback;

        $reflMethod = new \ReflectionMethod($object, $method);

        if (! $reflMethod->isPublic()) {
            throw new CallbackException(get_class($object) . '::' . $method . ' is not public');
        }

        return call_user_func_array([$object, $method], $this->parameters);
    }

    protected function invoke() 
    {
        return call_user_func_array($this->callback, $this->parameters);
    }

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

        return new $className;
    }

    protected function isArrayOfStrings($var) : bool
    {
        if (! is_array($var)) {
            return false;
        }

        $filtered = array_filter($var, 'is_string');
        return count($var) == count($filtered);
    }
}
