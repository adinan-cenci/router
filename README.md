# Yet another router

A basic router inspired by <a href="https://github.com/bramus/router" target="_blank">bramus/router</a> implementation.  
Check his project out for a more complete solution.

## How it works

Instantiate
```php
use \AdinanCenci\Router\Router;
$r = new Router();
```

Set the routes with regex patterns.
```php
$r->get(['/^$/', '/home/'], function() 
{
    echo 
    'This is the home page.';
})

->get('/about-us$/', function() 
{
    echo 
    'This is the institutional page';
})

->add('get|post', '/contact$/', function() 
{
    echo 
    'This is the contact form page';
})

->set404(function($path) use($r) 
{
    $r->header404();
    echo 
    'Error 404, nothing found related to '.$path;
});
```

And set it to run, that is it
```php
$r->run();
```
