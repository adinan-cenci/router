# A small PHP router library

A simple PHP router.

## How it works

```php
// Instantiate

use \AdinanCenci\Router\Router;
$r = new Router();

//-------------------------------------------------------------

// Define the routes

$r->get(['#^$#', '#home/?$#'], function() // an anonymous functions
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
    echo 'Sending your e-mail...';
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

// And set it to run, that's it
$r->run();

```

See the contents of the "examples" directory for more details.



## Methods

### ::add($methods = '*', $patterns, $callback)

Defines a route and the respective callback. Note that only the callback of the first matching route will be executed.

- $methods: A string representing the http methods ( GET, POST, PUT, DELETE, OPTIONS and PATCH ) separated with \| or a single '*' for all of them. This parameter is also optional.
- $patterns: Regex or array of regex patterns to be tested against the requested URL.
- $callback: An anonymous function, the name of a function or the method of a class. The router will attempt to instantiate classes in order to call non-static methods. Capture groups in the regex patterns will be passed as parameters to the callback.

```php
// Examples
$r->add('#home$#', function() 
{
    echo 'This callback will be executed 
    on all http requests on routes ending with "home".';
});

$r->add('get|post', '#about$#', function() 
{
    echo 'This callback will be executed 
    only on get/post request on routes ending with "about".';
});

$r->add('get|post', ['#user/(\w+)$#', '#u/(\w+)$#'], function($handle) 
{
    echo 'This callback will be executed 
    only on get/post request on routes ending with "user/'.$handle.'" or "u/'.$handle.'"' ;
});
```

### ::add shorthands

```php 
// Examples
$r->get('#home#', $call);     /* is the same as */ $r->add('get', '#home#', $call);
$r->post('#home#', $call);    /* is the same as */ $r->add('post', '#home#', $call);
$r->put('#home#', $call);     /* is the same as */ $r->add('put', '#home#', $call);
$r->delete('#home#', $call);  /* is the same as */ $r->add('delete', '#home#', $call);
$r->options('#home#', $call); /* is the same as */ $r->add('options', '#home#', $call);
$r->patch('#home#', $call);   /* is the same as */ $r->add('patch', '#home#', $call);
```

### ::set404($callback)

Define a method to call when all defined routes fail to match against the requested URL. The $callback function will receive by parameter the unmatched route.

```php
// Example
$r->set404(function($route) 
{
    echo 'Error 404, nothing found related to '.$route;
});
```

### ::before($methods = '*', $patterns, $callback)

Defines a middle-ware and the respective callback. The middle-wares will be matched against the requested url before the actual routes, and unlike the routes, more than one middle-ware callback may be executed. It accepts the the same parameter as ::add()

```php
// Example
$r->before('*', 'restricted-area', function() 
{
    if (! userIsLogged()) {
        header('Location: /login'); 
    }
});
```

### ::run()

Executes the router.

First it will try to match the request url and http method to <u>all</u> middle-wares, then it follows with the proper routes. 

Unlike the middle-wares, the router will execute the callback of the first matching route and stop.

It will throw an exception if unable to execute the callback associated.

### ::namespace($namespace)

Set the default namespace, so there will be no need to write the entire class name of the callback when defining the routes.

```php
// Example
$r->namespace('\MyProject\\');

$r->add('#home#', 'MyClass::method');
// Will assume \MyProject\MyClass::method()
```

### ::header404($replace = true, $responseCode = 404)

Just a helpful static method to send a 404 header.

```php
Router::header404(); // -> HTTP/1.0 404 Not Found
```

## License

MIT