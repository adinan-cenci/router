<?php 

function namedFunction($request, $handler) 
{
    echo html('This is a named function');
}

class SomeClass 
{
    public function __invoke($request, $handler) 
    {
        echo html('Instantiating an object and invoking it');
    }

    public static function staticMethod($request, $handler) 
    {
        echo html('This is a static method');
    }

    public function method($request, $handler) 
    {
        echo html('This is a method');
    }

    protected function protectedMethod($request, $handler) 
    {
        echo html('This is a protected method');
    }
}

class AnotherClass 
{
    public function __construct($foo, $bar) {}

    public function __invoke($request, $handler) 
    {
        echo html('Invoking an object');
    }
}