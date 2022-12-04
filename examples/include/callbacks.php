<?php 

function namedFunction($request, $handler) 
{
    echo html('This is a named function');
}

class SomeClass 
{
    public static function staticMethod($request, $handler) 
    {
        echo html('This is a static method');
    }

    public function method($request, $handler) 
    {
        echo html('This is a method');
    }
}