<?php 
use \AdinanCenci\Router\Router;

error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../vendor/autoload.php';
require 'include/functions.php';
require 'include/callbacks.php';

//----------------

$router = new Router();

// Anonymous function
$router->add('get', '#anonymous-function$#', function($request, $handler) 
{
    echo html('This is an anonymous function');
});

$router->add('get', '#named-function$#', 'namedFunction');

$router->add('get', '#static-method$#', 'SomeClass::staticMethod');



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



$router->run();
