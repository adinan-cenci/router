<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*------*/

require '../src/Router.php';
use \AdinanCenci\Router\Router;

/*------*/

$r = new Router();

/*------*/

$uri        = $r->getUri();
$url        = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
$baseHref   = rtrim(str_replace($uri, '', $url), '/').'/';
require 'resources/header.php';

/*------*/

$r->get('/^$/', function() {
    echo 'Home page';
})

->get('/about-us$/', function() {
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

->get('/contact\/?$/', function(){
    echo 
    '<h1>Contact page</h1>';
})

->set404(function($uri) {
    echo 
    '<h1>Error 404</h1>
    <p>Not found related to "'.$uri.'"</p>';
});

/*------*/

$r->run();

/*------*/

require 'resources/footer.html';