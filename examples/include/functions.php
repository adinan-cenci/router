<?php 
function html($html) 
{
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
            <a href="named-function">named function</a>
            <a href="static-method">static method</a>
            <a href="method">method</a>
            <a href="__invoke">invoke</a>
            <a href="file">file</a>
        </div>
        <div>
            <a href="non-existing-function">non existing unction</a>
            <a href="undefined-static-method">undefined static method</a>
            <a href="protected-method">protected-method</a>
            <a href="class-with-dependencies">class with dependencies</a>
            
        </div>
    </nav>
    <body>'.$html.'</body></html>';
}

function userIsLoggedIn() 
{
    return false;
}