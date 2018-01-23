<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*------*/

require '../src/Router.php';
use \AdinanCenci\Router\Router;

/*------*/

require 'resources/header.html';

/*------*/

$r = new Router();

/*------*/

$r->get('/^$/', function() {
    echo 'Home page';
})

->get('/contact\/?$/', function(){
    echo 'Contact page';
})

->get('/services\/?$/', function(){
    echo 'Services';
});

/*------*/

$r->run('');

/*------*/

require 'resources/footer.html';