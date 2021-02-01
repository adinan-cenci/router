# A small PHP router library

A simple PHP router to handle http requests.

- [How it works](#how-it-works)
- [Methods](#methods)
  - [::add() Add routes](#addmethods---patterns-callback)
  - [::add() Shorthands](#add-shorthands)
  - [::set404() No match found](#set404callback)
  - [::before() Middleware](#beforemethods---patterns-callback)
  - [::namespace()](#namespacenamespace)
  - [::header404()](#header404replace--true-responsecode--404) 
  - [::passParametersAsArray($bool = true)](#passParametersAsArray)
  - [::run()](#run)
  - [::parameter($index, $alternative = null)](#parameterindex-alternative--null)
- [Working inside subdirectories](#working-inside-subdirectories)
- [Server configuration](#server-configuration)
  - [Apache](#apache)
  - [Nginx](#nginx)
  - [IIS](#iis)
- [Installing](#installing)
- [License](#license)



## How it works

```php
// Instantiate

use \AdinanCenci\Router\Router;
$r = new Router();

//---Defining the routes----------------------

$r->get(['#^$#', '#home$#'], function() // an anonymous function
{
    echo 'This is the home page.';
})

//-------------

->get('#about-us$#', 'aboutPage') // a named function


//-------------

// Methods of classes, if the method is not static, 
// the router wil try to instantiate an object to 
// call the method

->get('#product/(\d+)$#',  'Product::getProduct') 
->post('#product/(\d+)$#', 'Product::saveProduct')
    
//-------------
    
->set404(function($uri) 
{
    Router::header404();
    echo 'Error 404, nothing found related to '.$uri;
});

//-------------

// And set it to run, that's it
$r->run();

```

See the contents of the "examples" directory for more details.
<br><br><br>  
## Methods

### ::add($methods = '*', $patterns, $callback)

Defines a route and the respective callback. Note that only the callback of the first matching route will be executed.

- $methods: A string representing the http methods ( GET, POST, PUT, DELETE, OPTIONS and PATCH ) separated with \| or a single '*' for all of them. This parameter is also optional.
- $patterns: Regex or array of regex patterns to be tested against the requested URI.
- $callback: An anonymous function, the name of a function, the method of a class or the path to a file to be required. The router will attempt to instantiate classes in order to call non-static methods. Capture groups in the regex patterns will be passed as parameters to the callback. If the callback is a valid path to a file, the captured groups will be available inside an array called `$parameters`.

```php
// Examples
$r->add('#home$#', function() 
{
    echo 'This callback will be executed 
    on all http requests with URIs ending with "home".';
});

$r->add('get|post', '#about$#', function() 
{
    echo 'This callback will be executed 
    only on get/post request with URIs ending with "about".';
});

$r->add('get|post', ['#user/(\w+)$#', '#u/(\w+)$#'], function($handle) 
{
    echo 'This callback will be executed 
    only on get/post request with URIs ending with "user/'.$handle.'" or "u/'.$handle.'"' ;
});
```
<br><br>  
### ::add() shorthands

```php 
// Examples
$r->get('#home#', $call);     /* is the same as */ $r->add('get', '#home#', $call);
$r->post('#home#', $call);    /* is the same as */ $r->add('post', '#home#', $call);
$r->put('#home#', $call);     /* is the same as */ $r->add('put', '#home#', $call);
$r->delete('#home#', $call);  /* is the same as */ $r->add('delete', '#home#', $call);
$r->options('#home#', $call); /* is the same as */ $r->add('options', '#home#', $call);
$r->patch('#home#', $call);   /* is the same as */ $r->add('patch', '#home#', $call);
```
<br><br>  
### ::set404($callback)

Define a method to call when all defined routes fail to match against the requested URI. The $callback function will receive by parameter the unmatched uri.

```php
// Example
$r->set404(function($uri) 
{
    echo 'Error 404, nothing found related to '.$uri;
});
```
<br><br>  
### ::before($methods = '*', $patterns, $callback)

Defines a middleware and the respective callback. The middlewares will be matched against the requested URI before the actual routes, and unlike the routes, more than one middleware callback may be executed. It accepts the the same parameter as ::add()

```php
// Example
$r->before('*', '#restricted-area#', function() 
{
    if (! userIsLogged()) {
        header('Location: /login'); 
    }
});
```
<br><br>  
### ::passParametersAsArray($bool = true)
By default the captured groups will be passed as individual parameters to the callbacks. By calling 
this method they will instead be passed in a single associative array.
```php
// Default behaviour:
$r->add('#products/(?<category>\d+)/(?<id>\d+)#', function($category, $id) 
{
    echo $category.', '.$id;
});


// As a single array:
$r->passParametersAsArray();


$r->add('#products/(?<category>\d+)/(?<id>\d+)#', function($parameters) 
{
    echo $parameters['category'].', '.$parameters['id'];
});

```
<br><br>  

### ::setNamespace($namespace)

Set the default namespace, so there will be no need to write the entire class name of the callback when defining the routes.

```php
// Example
$r->setNamespace('\MyProject\\');

$r->add('#home#', 'MyClass::method');
// The router will assume it refers to \MyProject\MyClass::method()
```
<br><br>  
### ::header404($replace = true, $responseCode = 404)

Just a helpful static method to send a 404 header.

```php
Router::header404(); // -> HTTP/1.0 404 Not Found
```
<br><br>  
### ::run()

Executes the router. First it will try to match the request URI and http method to <u>all</u> middlewares, 
then it follows with the proper routes. 

Unlike the middlewares, the router will execute the callback of the first matching route and stop.

It will throw an exception if unable to execute the callback associated.
<br><br><br>  

### ::parameter($index, $alternative = null)

Besides beign passed as parameters to the callbacks, the capture groups can also be accessed through this 
method.
<br><br><br>  

## Working inside subdirectories

The router will automatically work inside sub-folders. Consider the example:
Your URL: `http://yourwebsite.com/foobar/about`
If your router is inside `/www/foobar/`, the router will match the routes against `about` and <u>**not**</u> `foobar/about`.

Still, if you need to work with `foobar/about` instead, then you must pass `/www/` as your base directory to the Router class' constructor.

```php
//               /www/foobar/index.php
$r = new Router('/www/');
```
<br><br>  
## Server configuration

In order for it to work, we need to rewrite the requests to the file containing our router. Below are some examples:  


### Apache
Here is the example of a .htaccess for Apache:
```
RewriteEngine on

# Condition: Requested resource does not exist
RewriteCond %{SCRIPT_FILENAME} !-f
RewriteCond %{SCRIPT_FILENAME} !-d

# Rewrite to index.php
RewriteRule ^.{1,}$   index.php   [QSA]
```
<br><br>  

### Nginx
Here is the example for nginx:
```
location / {
    if ($script_filename !~ "-f") {
        rewrite "^/.{1,}$" /index.php;
    }
}
```
<br><br>  

### IIS
Here is the example of a web.config for Microsoft IIS:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="RewriteNonExistingFiles">
                    <match url="^.{1,}$" />
                    <conditions>
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
                    </conditions>
                    <action type="Rewrite" url="/index.php" appendQueryString="true" />
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>
```
<br><br>  


## Installing
Use composer
```
composer require adinan-cenci/router
```
<br><br>  


## License

MIT