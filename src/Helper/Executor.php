<?php 
namespace AdinanCenci\Router\Helper;

class Executor 
{
    protected $callback;

    protected array $parameters;

    public function __construct($callback, array $parameters) 
    {
        $this->callback   = $callback;
        $this->parameters = $parameters;
    }

    public function callIt() 
    {
        if (is_string($this->callback)) {
            return file_exists($this->callback)
                ? $this->callFile($this->callback)
                : $this->callString($this->callback);
        }

        if (is_callable($this->callback)) {
            return call_user_func_array($this->callback, $this->parameters);
        }

        var_dump($this->callback);
    }

    //------------------------------------------------------

    protected function callFile(string $file) 
    {
        if (! is_file($file)) {
            throw new \RuntimeException($file . ' is not a file.');
        }

        return include($file);
    }

    protected function callString(string $string) 
    {
        if ($this->isValidMethodName($string)) {
            return $this->callMethodName($string);
        }

        if ($this->isValidFunctionName($string)) {
            return class_exists($string)
                ? $this->invokeClass($string)
                : $this->callFunctionName($string);
        }

        throw new \RuntimeException($string . ': unable to execute callback');
    }

    protected function callFunctionName(string $functionName) 
    {
        if (! function_exists($functionName)) {
            throw new \RuntimeException($functionName . ' is undefined');
        }

        return call_user_func_array($functionName, $this->parameters);
    }

    protected function invokeClass(string $className) 
    {
        if (! $this->methodExists($className, '__invoke')) {
            throw new \RuntimeException($methodName . ' has no __invoke method');
        }

        $instance = $this->attemptToInstantiate($className);
        call_user_func_array($instance, $this->parameters);
    }

    protected function callMethodName(string $methodName) 
    {
        list($class, $method) = $this->separateClassAndMethod($methodName);

        if (! $this->methodExists($class, $method)) {
            throw new \RuntimeException($methodName . ' is undefined');
        }

        if (! $this->isPublicMethod($class, $method)) {
            throw new \RuntimeException($methodName . ' is not public');
        }

        return $this->isStaticMethod($class, $method)
            ? $this->callStaticMethod($class, $method)
            : $this->callMethod($class, $method);
    }

    protected function callMethod(string $className, string $methodName) 
    {
        $instance = $this->attemptToInstantiate($className);
        return call_user_func_array([$instance, $methodName], $this->parameters);
    }

    protected function callStaticMethod(string $className, string $methodName) 
    {
        return call_user_func_array([$className, $methodName], $this->parameters);
    }


    protected function separateClassAndMethod(string $methodName) : array
    {
        return explode('::', $methodName);
    }

    protected function isValidFunctionName(string $name) : bool 
    {
        return preg_match('/^\\\?[a-z][\w_\\\]+$/i', $name);
    }

    protected function isValidMethodName(string $name) : bool 
    {
        return preg_match('/^\\\?[a-z][\w_\\\]+::[a-z][\w_\\\]+$/i', $name);
    }

    protected function attemptToInstantiate(string $className) 
    {
        if ($this->isAbstract($className)) {
            throw new \RuntimeException($className . ' is abstract');
        }

        $refClass = new \ReflectionClass($className);
        $rfConstructor = $refClass->getConstructor();

        if ($rfConstructor && $rfConstructor->getNumberOfParameters()) {
            throw new \RuntimeException($className . ': I do not know how to instantiate it');
        }

        return new $className;
    }

    protected function isAbstract(string $className) : bool
    {
        return (new \ReflectionClass($className))->isAbstract();
    }

    protected function methodExists($objectOrClass, string $methodName) : bool
    {
        return (new \ReflectionClass($objectOrClass))->hasMethod($methodName);
    }

    protected function isStaticMethod($objectOrClass, string $methodName) : bool
    {
        return (new \ReflectionMethod($objectOrClass, $methodName))->isStatic();
    }

    protected function isPublicMethod($objectOrClass, string $methodName) : bool
    {
        return (new \ReflectionMethod($objectOrClass, $methodName))->isPublic();
    }
}
