<?php 
use \AdinanCenci\Router\Router;

error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../vendor/autoload.php';
require 'include/functions.php';

//----------------

$router = new Router();

require 'include/callbacks.php';
$router->add('get', '#^an-anonymous-function$#', function($request, $handler) {
    echo html('This is an anonymous function');
});
$router->add('get', '#^a-named-function$#', 'namedFunction');
$router->add('get', '#^a-static-method$#', 'SomeClass::staticMethod');
$router->add('get', '#^the-method-of-a-class$#', 'SomeClass::method');
$router->add('get', '#^__invoke$#', 'SomeClass');
$object = new AnotherClass('foo', 'bar');
$router->add('get', '#^an-object$#', $object);
$router->add('get', '#file$#', 'include/file.php');

// Errors
$router->add('get', '#^non-existing-function$#', 'nonExistingFunction');
$router->add('get', '#^undefined-static-method$#', 'SomeClass::undefinedStaticMethod');
$router->add('get', '#^protected-method$#', 'SomeClass::protectedMethod');
$router->add('get', '#^class-with-dependencies$#', 'AnotherClass');


$router->middleware('*', '#^admin/?#', function($request, $handler) 
{
    if (! userIsLoggedIn()) {
        return $handler->responseFactory
        ->movedTemporarily($handler->getUrl('login'));
    }
});

$router->add('get', '#login$#', function() 
{
    echo html(
    '<label>Username:</label><input type="text" name="username"/><br>
    <label>Password:</label><input type="password" name="password"/><br>
    <input type="submit" value="Login"/>');
});

$router->setExceptionHandler(function($handler, $exception) 
{
    return $handler->responseFactory->ok(html('ERROR!!! ' . $exception->getMessage()));
});


$router->run();
