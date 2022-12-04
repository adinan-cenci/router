<?php 
use \AdinanCenci\Router\Router;

error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../vendor/autoload.php';

//----------------

function html($html) 
{
    return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" /><title>Document</title><style>body{background: black; color: white;}</style></head><body>'.$html.'</body></html>';
}

function userIsLoggedIn() 
{
    return false;
}

$router = new Router();

$router->middleware('*', '#^admin/?#', function($request, $handler) 
{
    if (! userIsLoggedIn()) {
        return $handler->responseFactory
        ->movedTemporarily($handler->getUrl('login'));
    }
});

$router->add('get', '#home$#', function() 
{
    echo html('Home page');
});

$router->add('get', '#login$#', function() 
{
    echo html(
    '<label>Username:</label><input type="text" name="username"/><br>
    <label>Password:</label><input type="password" name="password"/><br>
    <input type="submit" value="Login"/>');
});

$router->run();
