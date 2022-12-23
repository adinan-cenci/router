<?php 
function html($html = '') 
{
    global $router;

    return '<!DOCTYPE html><html lang="en">
    <head><meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
    <style>
    body{font-family: Roboto, sans-serif; margin: 0px; background: black; color: white;}
    nav {
        margin-bottom: 20px;
    }
    nav div {
        display: flex;
    }
    nav a {
        flex-grow: 1;
        display: block;
        padding: 10px 20px;
        text-align: center;
        color: white;
        background: rgba(255, 255, 255, 0.1);
    }
    nav a:nth-child(2n+1) {
        background: rgba(255, 255, 255, 0.15);
    }

    </style>
    </head>
    <nav>        
        <div>
            <a href="a-non-existing-function">a non existing unction</a>
            <a href="an-undefined-method">undefined static method</a>
            <a href="a-protected-method">protected-method</a>
            <a href="a-class-with-dependencies">class with dependencies</a>
            
        </div>
    </nav>
    <ul>
        <li><a href="' . $router->getUrl('the-router/accepts/anonymous-functions') . '">anonymous function</a></li>
        <li><a href="' . $router->getUrl('the-router/accepts/named-functions') . '">named function</a></li>
        <li><a href="' . $router->getUrl('the-router/accepts/static-methods') . '">static method</a></li>
        <li><a href="' . $router->getUrl('the-router/accepts/methods') . '">method of a class</a></li>
        <li><a href="' . $router->getUrl('the-router/accepts/an-object-and-its-method') . '">the method of an object</a></li>
        <li><a href="' . $router->getUrl('the-router/accepts/objects') . '">objects</a></li>        
        <li><a href="' . $router->getUrl('the-router/accepts/classes') . '">invoke</a></li>
        
        <li><a href="' . $router->getUrl('the-router/accepts/psr-15-middlewares') . '">a middleware</a></li>
        <li><a href="' . $router->getUrl('the-router/accepts/files') . '">a file</a></li>
    </ul>

    <body>'.$html.'</body></html>';
}


function userIsLoggedIn($request) 
{
    return isset($request->getCookieParams()['loggedIn']);
}