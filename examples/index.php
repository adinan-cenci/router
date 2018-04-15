<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

//----------------

require '../src/Router.php';
require 'callbacks.php';
use \AdinanCenci\Router\Router;

$r = new Router();

//----------------

$content    = '';
$path       = $r->getPath();
$baseHref   = $r->getBaseHref();
$r->setNamespace('\Example\\');

//----------------

// you may set more than one pattern as to create aliases
$r->get(['/^$/', '/home$/'], function() use ($r)
{

    if (! isset($_GET['foo'])) {
        header('Location: '.$r->getBaseHref().'home/?foo=bar');
        return;
    }

    $GLOBALS['content'] .= 
    '<h1>Home page</h1>
    <p>Welcome! This is a example of how the Router works.</p>
    <p>Just type something in the address bar, it shall be matched with the specified routes.<br> 
    If no match is found, it will trigger a 404 error.</p>';

    $GLOBALS['content'] .= 
    '<table>
        <tr><th>URL:</th><td>'.$r->getUrl().'</td></tr>
        <tr><th>BASE HREF:</th><td>'.$r->getBaseHref().'</td></tr>
        <tr><th>PATH:</th><td>'.$r->getPath().'</td></tr>
    </table>';
})

->get('/about-us$/', function() 
{
    $GLOBALS['content'] .= 
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
    $GLOBALS['content'] .= 
    '<h1>Products</h1>
    <ul>
        <li><a href="product/1">Wireless Mouse</a></li>
        <li><a href="product/2">Gaming keyboard</a></li>
        <li><a href="product/3">Wide monitor</a></li>
    </ul>';
})

->get('#product/(\d+)$#', function($id) 
{
    $GLOBALS['content'] .= 
    '<h1>ID: '.$id.'</h1>
    <a href="products">< go back</a>';
})

->add('get|post', '/contact$/', function() 
{
    $GLOBALS['content'] .= 
    '<h1>Contact page</h1>';

    if (! empty($_POST['name'])) {
        echo '<p class="success">Message sent!</p>';
    }

    $GLOBALS['content'] .= 
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

->get('#class/method#', 'Controller::publicMethod')

->get('#class/protected-method#', 'Controller::protectedMethod')

->get('#class/static-method#', 'Controller::staticMethod')

->get('#function#', 'myFunction')

->set404(function($path) 
{
    Router::header404();
    
    $GLOBALS['content'] .= 
    '<h1>Error 404</h1>
    <p>Nothing found related to "'.$path.'"</p>';
});

//----------------

try {
    $r->run();
} catch (Exception $e) {
    $GLOBALS['content'] .=  
    '<h1>EXCEPTION ERROR!</h1>
    <p>'.$e->getMessage().'</p>';
}

//----------------

require 'resources/header.php';

echo 
$content;

require 'resources/footer.html';
