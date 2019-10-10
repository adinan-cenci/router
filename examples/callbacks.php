<?php 
namespace Example;

class Controller 
{
    public function publicMethod() 
    {
        $GLOBALS['content'] .=  
        '<h1>Public method</h1>
        <p>This is a public method of an instantiated object!</p>';
    }

    protected function protectedMethod() 
    {
        $GLOBALS['content'] .= 
        '<h1>Protected method</h1>
        <p>You can\'t access this, this is a protected method</p>';
    }

    public static function staticMethod() 
    {
        $GLOBALS['content'] .=  
        '<h1>Static method</h1>
        <p>This is a public static method!</p>';
    }
}

function myFunction() 
{
    $GLOBALS['content'] .=  
    '<h1>Function</h1>
    <p>This is a function</p>';
}
