<?php 
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function homePage($request, $handler) 
{
    echo html('<h1>Router examples</h1>Here we will see some examples of how to use this library.');
}

function namedFunction($request, $handler) 
{
    echo html('<h1>A named function</h1>The router accepts named function, namespaced or not.');
}

class SomeClass 
{
    public function __invoke($request, $handler) 
    {
        echo html('<h1>An object</h1>If you inform just an object, the router will attempt to call the <code>__invoke()</code> method.');
    }

    public static function staticMethod($request, $handler) 
    {
        echo html("<h1>A static method</h1>The router accept static methods. You may specify them in a 
        single string: <code>'Namespace\Class::andMethod'</code> or in an array: <code>['Namespace\Class', 'andMethod']</code>");
    }

    public function method($request, $handler) 
    {
        echo html('<h1>A public method</h1>It works just like static methods: single string or arrays. The router will attempt to 
        instantiate an object.');
    }

    public function methodOfAnObject($request, $handler) 
    {
        echo html("<h1>An object and method</h1>The router accepts an object and the method to be called.");
    }

    protected function protectedMethod($request, $handler) 
    {
        echo html('This is a protected method');
    }
}

class AnotherClass 
{
    public function __invoke($request, $handler) 
    {
        echo html('<h1>A class</h1>If you inform just name of the class, the router will attempt to instnatiate an object and call the <code>__invoke()</code> method.');
    }
}

class YetAnotherClass 
{
    public function __construct($foo, $bar) {}

    public function __invoke() {}
}

function loginPage($request, $handler) 
{
    if (userIsLoggedIn($request)) {
        return $handler->responseFactory
        ->movedTemporarily($handler->getUrl('admin'));
    }

    $username = $request->getParsedBody()['username'] ?? '';
    $password = $request->getParsedBody()['password'] ?? '';

    if (! empty($username)) {
        return $handler->responseFactory
        ->movedTemporarily($handler->getUrl('admin'))
        ->withAddedCookie('loggedIn', 'true');
    }

    return html(
    '<h1>Login</h1>
    <form method="post" action="' . $handler->getUrl('login') . '">
        <label>Username:</label><input type="text" name="username"/><br>
        <label>Password:</label><input type="password" name="password"/><br>
        <input type="submit" value="Login"/>
    </form>');
}

function adminPage($request, $handler) 
{
    return html('<h1>Admin page</h1><a href="' . $handler->getUrl('logout') . '">logout</a>');
}

function logoutPage($request, $handler) 
{
    return $handler->responseFactory
    ->movedTemporarily($handler->getUrl('login'))
    ->withAddedCookie('loggedIn', 'false', -1);
}

class Middleware implements \Psr\Http\Server\MiddlewareInterface 
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->responseFactory->ok(html('<h1>PSR-15</h1>It accepts middleware objects as well.'));
    }
}