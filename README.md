# Yet another router

A simple php router.

## How it works

```php
// Instantiate
use \AdinanCenci\Router\Router;
$r = new Router();

//----------------------

// Defining the routes
$r->add('get|post', ['#^$#', '#home/?$#'], function() 
{
    echo 'This is the home page.';
})

//-------------

->get('#about-us/?$#', 'aboutPage') // a function

//-------------
    
->get('#contact/?$#', function() 
{
    echo 'This is the contact form page';
})    
->post('#contact/?$#', function() 
{
    echo 'Sending e-mail.';
})

//-------------

->get('#product/(\d+)$#', 'Product::getProduct') // Object's method
->post('#product/(\d+)$#', 'Product::saveProduct')
    
//-------------
    
->set404(function($path) 
{
    Router::header404();
    echo 'Error 404, nothing found related to '.$path;
});

//-------------

// And set it to run, that is it  
$r->run();
```



## Methods

| Method                                            | Description                                                  |
| ------------------------------------------------- | ------------------------------------------------------------ |
| ::add($methods = '*', $patterns, $callback)       | Defines routes and the respective callback.<br />$methods: A string for the http methods ( get, post, put and delete ) separated with \| or '*' for all four.<br />$pattern: Regex or array of regex patterns to be tested against the requested URL.<br />$callback: A function, the name of a function or method. The router will attempt to instantiate classes in order to call non-static methods.<br />Capture groups in the regex patterns will be passed as parameters to the callback. |
| ::get($patterns, $callback)                       | Shorthand for ::add('get', $patterns, $callback);            |
| ::post($patterns, $callback)                      | ...                                                          |
| ::put($patterns, $callback)                       | ...                                                          |
| ::delete($patterns, $callback)                    | ...                                                          |
| ::set404($callback)                               | Define a method to deal with the request when all defined routes fail to match against the requested URL.<br />The $callback function will receive by parameter the unmatched route. |
| ::run()                                           | Will try to match the requested URL with the routes defined.<br />Will throw an exception if unable to call the callback associated. |
| ::namespace($namespace)                           | Set the default namespace, so there will be no need to write the entire controller's class name when defining the routes. |
| ::header404($replace = true, $responseCode = 404) | A helpful static method to send a 404 header.                |

