<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*------*/

require '../src/Router.php';
use \AdinanCenci\Router\Router;

/*------*/

$r = new Router();

/*------*/

$path       = $r->getPath();
$baseHref   = $r->getBaseHref();
require 'resources/header.php';

/*------*/

// you may set more than one pattern as to create aliases
$r->get(['/^$/', '/home/'], function() 
{
    echo 
    '<h1>Home page</h1>
    <p>Welcome! This is a example of how the Router works.</p>
    <p>Just type something in the address bar, it shall be matched with the specified routes.
    If no match is found, it will trigger a 404 error.</p>
    ';
})

->get('/about-us$/', function() 
{
    echo 
    '<h1>About us</h1>
    <p>
        Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
        tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
        quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
        consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
        cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
        proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
    </p>
    ';
})

->get('/products/', function() 
{
    echo 
    '<h1>Products</h1>
    <ul>
        <li><a href="product/1">Wireless Mouse</a></li>
        <li><a href="product/2">Gaming keyboard</a></li>
        <li><a href="product/3">Wide monitor</a></li>
    </ul>';
})

->get('#product/(\d+)#', function($id) 
{
    echo 
    '<h1>'.$id.'</h1>';
})

->add('get|post', '/contact\/?$/', function() 
{
    echo 
    '<h1>Contact page</h1>';

    if (! empty($_POST['name'])) {
        echo '<p class="success">Message sent!</p>';
    }

    echo 
    '<form method="post">
        <label>
            Name:
            <input type="text" name="name"/>
        </label>
        <label>
            Phone:
            <input type="text" name="phone"/>
        </label>
        <label>
            E-mail:
            <input type="text" name="email"/>
        </label>
        <label>
            Message:
            <textarea name="message"></textarea>
        </label>
        <input type="submit" value="Send"/>
    </form>
    ';
})

->set404(function($path) use($r) 
{
    $r->header404();
    echo 
    '<h1>Error 404</h1>
    <p>Nothing found related to "'.$path.'"</p>';
});

/*------*/

$r->run();

/*------*/

require 'resources/footer.html';
