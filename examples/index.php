<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

//----------------

require '../vendor/autoload.php';
use \AdinanCenci\Router\Router;

//----------------


function userIsLogged() 
{
    return false;
}



$router = new Router();


$router->middleware('*', '#^admin/?#', function($request, $handler) 
{
    if (! userIsLogged()) {
        return $handler->responseFactory
        ->createResponse(302, 'not logged')
        ->withHeader('Location', '/router/examples/login');
    }
});


$router->add('get', '/home$/', function() 
{
    echo 'Home page';
});

$router->add('get', '/login$/', function() 
{
    echo 
    '<label>Username:</label><input type="text" name="username"/><br>
    <label>Password:</label><input type="password" name="password"/><br>
    <input type="submit" value="Login"/>';
});

$router->run();
