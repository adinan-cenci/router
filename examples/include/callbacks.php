<?php 
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function namedFunction($request, $handler) 
{
    echo html('This is a named function');
}

class SomeClass 
{
    public function __invoke($request, $handler) 
    {
        echo html('Instantiating an object and invoking it');
    }

    public static function staticMethod($request, $handler) 
    {
        echo html('This is a static method');
    }

    public function method($request, $handler) 
    {
        echo html('This is a method');
    }

    protected function protectedMethod($request, $handler) 
    {
        echo html('This is a protected method');
    }
}

class AnotherClass 
{
    public function __construct($foo, $bar) {}

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