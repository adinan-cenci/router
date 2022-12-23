<?php 
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function namedFunction($request, $handler) 
{
    echo html('The router accepts named function, namespaced or not.');
}

class SomeClass 
{
    public function __invoke($request, $handler) 
    {
        echo html('Instantiating an object and invoking it');
    }

    public static function staticMethod($request, $handler) 
    {
        echo html("The router accept static methods. You may specify them in a 
        single string: 'Namespace\Class::andEverything' or in an array: ['Namespace\Class', 'andMethod']");
    }

    public function method($request, $handler) 
    {
        echo html('It works just like static methods: single string or arrays. The router will attempt to 
        instantiate an object.');
    }

    public function methodOfAnObject($request, $handler) 
    {
        echo html("The router accepts an object and the method's name to be called.");
    }

    protected function protectedMethod($request, $handler) 
    {
        echo html('This is a protected method');
    }
}

class AnotherClass 
{
    public function __construct($foo = 'foo', $bar = 'bar') {}

    public function __invoke($request, $handler) 
    {
        echo html('Invoking an object');
    }

    public function method($request, $handler) 
    {
        echo html('This is a method');
    }
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
    '<form method="post" action="' . $handler->getUrl('login') . '">
    <label>Username:</label><input type="text" name="username"/><br>
    <label>Password:</label><input type="password" name="password"/><br>
    <input type="submit" value="Login"/>
    </form>');
}

function adminPage($request, $handler) 
{
    return html('Admin page <br> <a href="' . $handler->getUrl('logout') . '">logout</a>');
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
        return $handler->responseFactory->ok(html('This is a middleware'));
    }
}