<?php 
use \AdinanCenci\Router\Router;

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../vendor/autoload.php';
require 'include/functions.php';

//----------------

// Instantiate the router.
$router = new Router();

// Callbacks for this example.
require 'include/callbacks.php';

// Ok, what can I add as callbacks to the router ?
$router->add('get', '#^an-anonymous-function$#', function($request, $handler) {
    echo html('This is an anonymous function');
});
$router->add('get', '#^a-named-function$#', 'namedFunction');
$router->add('get', '#^the-method-of-a-class$#', 'SomeClass::method'); // The router will attempt to instantiate an object.
$router->add('get', '#^a-static-method$#', 'SomeClass::staticMethod');
$router->add('get', '#^just-a-class$#', 'SomeClass'); // The router will attempt to instantiate an object and call __invoke.
$router->add('get', '#^an-object$#', ($object = new AnotherClass('foo', 'bar'))); // The router will call __invoke.
$router->add('get', '#^the-method-of-an-object$#', [$object, 'method']);
$router->add('get', '#^a-file$#', 'include/file.php');

// Now let's see some errors,
// Those will throw exceptions
$router->add('get', '#^a-non-existing-function$#', 'nonExistingFunction');
$router->add('get', '#^an-undefined-method$#', 'SomeClass::undefinedMethod');
$router->add('get', '#^a-protected-method$#', 'SomeClass::protectedMethod');
$router->add('get', '#^a-class-with-dependencies$#', 'AnotherClass');

// How to handle exceptions
$router->setExceptionHandler(function($handler, $exception) 
{
    return $handler->responseFactory->internalServerError(html('<h1>Error</h1>' . $exception->getMessage()));
});

// While only one router will be executed, all matching middlewares will have their turn,
// unless if a middleware returns a response, then the router finishes earlier
$router->middleware('*', '#^admin/?#', function($request, $handler) 
{
    if (! userIsLoggedIn($request)) {
        return $handler->responseFactory
        ->movedTemporarily($handler->getUrl('login'));
    }

    //return $handler->handle($request);
});

$router->add('*', '#^login$#', 'loginPage');
$router->add('*', '#^logout$#', 'logoutPage');
$router->add('*', '#^admin$#', 'adminPage');


$router->run();
