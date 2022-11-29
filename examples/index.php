<?php 
use \AdinanCenci\Router\Router;

error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../vendor/autoload.php';

//----------------

function userIsLoggedIn() 
{
    return false;
}

$router = new Router();

$router->middleware('*', '#^admin/?#', function($request, $handler) 
{
    if (! userIsLoggedIn()) {
        return $handler->responseFactory
        ->createResponse(302, 'not logged')
        ->withHeader('Location', $handler->getUrl('examples/login'));
    }
});

$router->add('get', '#home$#', function() 
{
    echo 'Home page';
});

$router->add('get', '#login$#', function() 
{
    echo 
    '<label>Username:</label><input type="text" name="username"/><br>
    <label>Password:</label><input type="password" name="password"/><br>
    <input type="submit" value="Login"/>';
});

$router->run();
