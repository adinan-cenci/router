<?php 

function namedFunction($request, $handler) 
{
    echo html('This is a named function');
}

class SomeClass 
{
    public function __invoke($request, $handler) 
    {
        echo html('This is an __invoke method');
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
        echo html('This is an __invoke method');
    }
}