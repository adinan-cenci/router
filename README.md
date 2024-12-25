# A small PHP router library

A simple PHP router to handle http requests.

This library is [PSR-15](https://www.php-fig.org/psr/psr-15/) compliant and therefore works with [PSR-7](https://www.php-fig.org/psr/psr-7/) messages and makes use of [PSR-17](https://www.php-fig.org/psr/psr-17/) factories.

<br><br><br>

## Instantiating

```php
use AdinanCenci\Router\Router;
$r = new Router();
```

<br><br><br>

## Adding routes

You may add routes by informing the http method, the regex pattern to be matched against the the path and the associated controller:

```php
$r->add('get', '#home$#', 'controller');
```

### Http method

```php
// You may inform a single http method:
$r->add('get', '#home#', 'controller')

// Or several inside an array ...
$r->add(['get', 'post'], '#home#', 'controller')

// Or in a pipe separated string
$r->add('get|post', '#home#', 'controller')

// Or use an sterisk to match all methods.
$r->add('*', '#home#', 'controller')
```

### Regex patterns

A simple regex pattern. Capture groups will be passed to the controller as attributes.

**Obs**: The route accepts multiple patterns as an array.

```php
$r->add('*', '#products/(?<category>\d+)/(?<id>\d+)#', function($request, $handler) 
{
   $category  = $request->getAttribute('category', null);
   $productId = $request->getAttribute('id', null);
});
```

### Controllers

The controller will receive two paramaters: an instance of  `Psr\Http\Message\ServerRequestInterface` and `Psr\Http\Server\RequestHandlerInterface` respectively.

The routes accept various arguments as controllers:

```php
$r->add('get', '#anonymous-function$#', function($request, $handler) 
{
    echo 'Anonymous function';
})

//-------------

->add('get', '#named-function$#', 'namedFunction')

//-------------

->add('get', '#static-methods$#', ['MyClass', 'staticMethod'])
// A single string also works:
->add('get', '#static-methods$#', 'MyClass::staticMethod')

//-------------

// Of course, it also accepts instances of Psr\Http\Server\MiddlewareInterfac 
// ( see the PSR-15 specification for more information )
->add('get', '#psr-15$#', $middleware)

//-------------

->add('get', '#object-and-method$#', [$object, 'methodName'])

//-------------

->add('get', '#object$#', $object)
// The ::__invoke() magic method will be called.

//-------------

->add('get', '#class-and-method$#', ['MyClass', 'methodName'])
// It will attempt to instantiate the class first.
// A single string also works:
->add('get', '#class-and-method$#', 'MyClass::methodName')

//-------------

->add('get', '#class$#', ['MyClass'])
// It will attempt to instantiate the class and call the ::__invoke() magic method.
```

**Obs**: If the controller does not exist or cannot be called because of some reason or another, an exception will be thrown.

See the contents of the "examples" directory for more details.

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

<br><br><br>

## Executing

Calling `::run()` will execute the router and send a respose.

```php
$r->run();
```

<br><br><br>

## PSR compliance

This library is [PSR-15](https://www.php-fig.org/psr/psr-15/) compliant, as such your controllers may tailor the response in details as specified in the [PSR-7](https://www.php-fig.org/psr/psr-7/). The handler make [PSR-17](https://www.php-fig.org/psr/psr-17/) factories available to use.

```php
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

$r->add('get', '#home$#', function(ServerRequestInterface $request, RequestHandlerInterface $handler)
{
   // Psr\Http\Message\ResponseFactoryInterface instance.
   $responseFactory = $handler->responseFactory;

   // Returns an instance of ResponseInterface with code 200.
   return $responseFactory->createResponse(200, 'OK');
});
```

### **IMPORTANT**

If your controller does not return an instance of `ResponseInterface`, the router will create one based out of whatever was outputed through `echo` and `print`.

### Niceties

Besides being PSR-17 compliant, the default response factory comes with some methods to make things easier:

```php
$responseFactory = $handler->responseFactory;

// Response with code 200
$responseFactory->ok('your html here');

// Response with code 201
$responseFactory->created('your html here');

// Response with code 301
$responseFactory->movedPermanently('https://redirect.here.com');

// Response with code 302
$responseFactory->movedTemporarily('https://redirect.here.com');

// Response with code 400
$responseFactory->badRequest('your html here');

// Response with code 401
$responseFactory->unauthorized('your html here');

// Response with code 403
$responseFactory->forbidden('your html here');

// Response with code 404
$responseFactory->notFound('your html here');

// Response with code 500
$responseFactory->internalServerError('your html here');

// Response with code 501
$responseFactory->notImplemented('your html here');

// Response with code 502
$responseFactory->badGateway('your html here');

// Response with code 503
$responseFactory->serviceUnavailable('your html here');
```

Adding cookies to a response object is made easier with `::withAddedCookie()`:

```php
$response = $responseFactory->ok('your html here');

$expires  = null;  // optional
$path     = '';    // optional
$domain   = '';    // optional
$secure   = false; // optional
$httpOnly = false; // optional

$response = $response->withAddedCookie('cookieName', 'cookieValue', $expires, $path, $domain, $secure, $httpOnly);
```

<br><br><br>

## Middlewares

Middlewares will be processed before the routes. Middlewares are similar to routes but unlike routes more than one middleware may be executed.

```php
// Example
$r->before('*', '#restricted-area#', function($request, $handler) 
{
    if (! userIsLogged()) {
        return $handler->responseFactory->movedTemporarily('/login-page');
    }
});
```

<br><br><br>

## Errors

### Exceptions

By default catched exceptions will be rendered in a 500 response object, you may customize it by setting your own handler.

```php
$r->setExceptionHandler(function($request, $handler, $path, $exception) 
{
    return $handler->responseFactory
      ->internalServerError('<h1>Error 500 (' . $path . ')</h1><p>' . $exception->getMessage() . '</p>');
});
```

### Not found

By default when no route is found, the router will render a 404 response object, you may customize it by setting your own handler.

```php
$r->setNotFoundHandler(function($request, $handler, $path) 
{
    return $handler->responseFactory
      ->internalServerError('<h1>Error 404</h1><p>Nothing found related to "' . $path . '"</p>');
});
```

<br><br><br>

### ::setDefaultNamespace($namespace)

Set the default namespace, so there will be no need to write the entire class name of the controller when defining routes.

```php
// Example
$r->setDefaultNamespace('MyProject');

$r->add('get', '#home#', 'MyClass::method');
// If MyClass does not exist, the router will assume it refers to 
// MyProject\MyClass::method()
```

<br><br><br>

## Working inside sub-directories

The router will automatically work inside sub-directories.

Consider the example:
Your URL: `http://yourwebsite.com/foobar/about`

Your document root is  
`/var/www/html/` and your router is inside of  
`/var/www/html/foobar/`.

The router will match the routes against `about` and <u>**NOT**</u> `foobar/about`.

Still, if you really need to work with `foobar/about` , then you must pass `/var/www/html/` as your base directory to the Router class' constructor.

```php
//               /var/www/html/foobar/index.php
$r = new Router('/var/www/html/');
```

<br><br><br>

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

<br><br><br>

### Nginx

Here is the example for nginx:

```
location / {
    if ($script_filename !~ "-f") {
        rewrite "^/.{1,}$" /index.php;
    }
}
```

<br><br><br>

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

<br><br><br>

## Installing

Use composer

```
composer require adinan-cenci/router
```

<br><br><br>

## License

MIT