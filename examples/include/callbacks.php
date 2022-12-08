<?php 

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
    var_dump($request->getParsedBody());

    $username = $request->getParsedBody()['username'] ?? '';
    $password = $request->getParsedBody()['password'] ?? '';

    if (!empty($username)) {
        $_SESSION['username'] = $username;
        $_SESSION['password'] = $password;

        return $handler->responseFactory
        ->movedTemporarily($handler->getUrl('admin'));
    }

    return html(
    '<form method="post" action="' . $handler->getUrl('login') . '">
    <label>Username:</label><input type="text" name="username"/><br>
    <label>Password:</label><input type="password" name="password"/><br>
    <input type="submit" value="Login"/>
    </form>');
}