<?php 
function html($html = '') 
{
    global $router;

    return '<!DOCTYPE html><html lang="en">
    <head><meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
    <style>
    body{margin: 0px; font-family: Roboto, sans-serif; background: black; color: white;}
    
    
    .layout {
        display: flex;
    }
    .region {
        display: block;
        padding: 10px;
    }

    main {
        flex-grow: 1;
    }

    .menu {
        width: 260px;
    }

    .menu ul {
        margin: 0px;
        padding: 0px 0px 0px 16px;
    }
    code {
        display: inline-block;
        background: #444;
        border-radius: 5px;
        padding: 2px;
    }
    </style>
    </head>
    <body>
        <div class="layout">
            <div class="region menu">
                <p>What will work:</p>
                <ul>
                    <li><a href="' . $router->getUrl('/the-router/accepts/anonymous-functions') . '">anonymous function</a></li>
                    <li><a href="' . $router->getUrl('/the-router/accepts/named-functions') . '">named function</a></li>
                    <li><a href="' . $router->getUrl('/the-router/accepts/static-methods') . '">static method</a></li>
                    <li><a href="' . $router->getUrl('/the-router/accepts/methods') . '">class and method name</a></li>
                    <li><a href="' . $router->getUrl('/the-router/accepts/an-object-and-its-method') . '">an object and a method</a></li>
                    <li><a href="' . $router->getUrl('/the-router/accepts/objects') . '">an object</a></li>
                    <li><a href="' . $router->getUrl('/the-router/accepts/classes') . '">a class</a></li>   
                    <li><a href="' . $router->getUrl('/the-router/accepts/psr-15-middlewares') . '">a middleware</a></li>
                    <li><a href="' . $router->getUrl('/the-router/accepts/files') . '">a file</a></li>
                </ul>

                <p>A few more examples:</p>
                <ul>
                    <li><a href="' . $router->getUrl('/query-parameters?foo=something&bar=other thing') . '">query parameters</a></li>
                    <li><a href="' . $router->getUrl('/post-request') . '">post request</a></li>
                    <li><a href="' . $router->getUrl('/product/category-here/id-goes-here') . '">getting attributes from the url</a></li>
                </ul>
            </div>

            <main class="region">
                '.$html.'
            </main>

            <div class="region menu">
                <p>What will not:</p>
                <ul>
                    <li><a href="' . $router->getUrl('the-router/will-not-accept/an-undefined-function') . '">undefined functions</a></li>
                    <li><a href="' . $router->getUrl('the-router/will-not-accept/an-undefined-method') . '">undefined methods</a></li>
                    <li><a href="' . $router->getUrl('the-router/will-not-accept/a-protected-method') . '">protected methods</a></li>
                    <li><a href="' . $router->getUrl('the-router/will-not-accept/a-class-with-dependencies') . '">classes with dependencies</a></li>
                </ul>
            </div>
        </div>
    </body>
    </html>';
}


function userIsLoggedIn($request) 
{
    return isset($request->getCookieParams()['loggedIn']);
}