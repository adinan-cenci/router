<?php 
use \AdinanCenci\Router\Router;

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (! file_exists('../vendor/autoload.php')) {
    die('<h1>We need an autoload file for the examples to work.</h1>');
}

require '../vendor/autoload.php';
require 'include/functions.php';
require 'include/callbacks.php'; // Callbacks for this example are defined in this file.



/************************************************************
**** Instatiating
************************************************************/
$router = new Router();



/************************************************************
**** Routes
************************************************************/

// Ok, what can I add as a controller to the router ?
$router->add('get', '#^the-router/accepts/anonymous-functions$#', function ($request, $handler) {
    echo html('<h1>An anonymous function</h1>You may add anonymous functions directly to the router.');
});
$router->add('get', '#^the-router/accepts/named-functions$#', 'namedFunction');
$router->add('get', '#^the-router/accepts/static-methods$#', 'SomeClass::staticMethod');
$router->add('get', '#^the-router/accepts/methods$#', 'SomeClass::method'); // The router will attempt to instantiate an object.
$router->add('get', '#^the-router/accepts/objects$#', ($object = new SomeClass('foo', 'bar'))); // The router will call __invoke.
$router->add('get', '#^the-router/accepts/an-object-and-its-method$#', [$object, 'methodOfAnObject']);
$router->add('get', '#^the-router/accepts/classes$#', 'AnotherClass'); // The router will attempt to instantiate an object and call __invoke.
$router->add('get', '#^the-router/accepts/psr-15-middlewares$#', (new Middleware())); // a PSR-15 middleware, of course.
$router->add('get', '#^the-router/accepts/files$#', 'include/file.php');
$router->add('get', '#^$#', 'homePage');



/************************************************************
**** Middlewares
************************************************************/

// While only one router will be executed, all matching middlewares will have their turn,
// unless if a middleware returns a response, then the router finishes earlier.
$router->middleware('*', '#^admin/?#', function($request, $handler) 
{
    if (! userIsLoggedIn($request)) {
        return $handler->responseFactory
        ->movedTemporarily($handler->getUrl('login'));
    }
});

$router->add('*', '#^login$#', 'loginPage');
$router->add('*', '#^logout$#', 'logoutPage');
$router->add('*', '#^admin$#', 'adminPage');



/************************************************************
**** No router found
************************************************************/

// Customizing the 404 page
$router->setNotFoundHandler(function($request, $handler, $pathOverride) 
{
    return $handler->responseFactory->notFound(html('<h1>404 Nothing found</h1>related to "' . $pathOverride . '"'));
});



/************************************************************
**** Dealing with errors
************************************************************/

// Now let's see some errors,
$router->add('get', '#^the-router/will-not-accept/an-undefined-function$#', 'nonExistingFunction');
$router->add('get', '#^the-router/will-not-accept/an-undefined-method$#', 'SomeClass::undefinedMethod');
$router->add('get', '#^the-router/will-not-accept/a-protected-method$#', 'SomeClass::protectedMethod');
$router->add('get', '#^the-router/will-not-accept/a-class-with-dependencies$#', 'YetAnotherClass');


// How to handle exceptions
$router->setExceptionHandler(function($handler, $exception) 
{
    return $handler->responseFactory->internalServerError(html('<h1>Error</h1>' . $exception->getMessage()));
});



/************************************************************
**** Executing
************************************************************/

$router->run();
