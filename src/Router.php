<?php
namespace AdinanCenci\Router;

use AdinanCenci\Router\Helper\Server;
use AdinanCenci\Router\Helper\File;
use AdinanCenci\Router\Routing\RouteCollection;
use AdinanCenci\Router\Routing\Route;
use AdinanCenci\Router\Exception\CallbackException;

use AdinanCenci\Psr7\Uri;
use AdinanCenci\Psr17\ServerRequestFactory;
use AdinanCenci\Psr17\ResponseFactory;
use AdinanCenci\Psr17\StreamFactory;
use AdinanCenci\Psr17\Helper\Globals;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @property-read AdinanCenci\Psr17\ResponseFactory $responseFactory
 * @property-read AdinanCenci\Psr17\StreamFactory $streamFactory
 */
class Router implements RequestHandlerInterface 
{
    /**
     * @var string $baseDirectory 
     *   Absolute path to the router's base directory. It will be used to 
     *   determine the path that will be matched against the routes.
     */
    protected string $baseDirectory;

    /**
     * @var string $defaultNamespace 
     *   Default namespace for the controllers, just so we don't need to
     *   write them repeteadely over and over.
     */
    protected string $defaultNamespace = '';

    /** @var AdinanCenci\Router\Routing\RouteCollection */
    protected RouteCollection $middlewareCollection;

    /** @var AdinanCenci\Router\Routing\RouteCollection */
    protected RouteCollection $routeCollection;

    /** @var AdinanCenci\Router\Routing\Route[] */
    protected ?array $matchingMiddlewares = null;

    /** @var AdinanCenci\Psr17\ResponseFactory */
    protected ResponseFactory $responseFactory;

    /** @var AdinanCenci\Psr17\StreamFactory */
    protected StreamFactory $streamFactory;

    /** 
     * @var callable $exceptionHandler 
     *   A function to deal with exceptions.
     *   The callback function will receive 4 parameters:
     *   callback(ServerRequestInterface $request, RequestHandlerInterface $handler, string $path, \Exception $exception)
     */
    protected $exceptionHandler;

    /** 
     * @var callable $exceptionHandler 
     *   A function to be called when no route is found.
     *   The callback function will receive 3 parameters:
     *   callback(ServerRequestInterface $request, RequestHandlerInterface $handler, string $path)
     */
    protected $notFoundHandler;

    /**
     * @param string|null $baseDirectory
     *   Absolute path. If not informed, the current file's parent directory 
     *   will be used.
     */
    public function __construct(?string $baseDirectory = null) 
    {
        $this->baseDirectory = $baseDirectory
            ? File::trailingSlash(File::forwardSlash($baseDirectory))
            : File::getParentDirectory(Server::getCurrentFile());

        $this->responseFactory      = new ResponseFactory();
        $this->streamFactory        = new StreamFactory();

        $this->middlewareCollection = new RouteCollection();
        $this->routeCollection      = new RouteCollection();

        // Default handler.
        $this->setExceptionHandler(function($request, $handler, $path, $exception) 
        {
            return $handler
                ->responseFactory
                ->internalServerError('<h1>Error 500</h1><p>' . $exception->getMessage() . '</p>');
        });

        // Default handler.
        $this->setNotFoundHandler(function($request, $handler, $path) 
        {
            return $handler
                ->responseFactory
                ->notFound('<h1>Error 404</h1><p>Nothing found related to "' . $path . '"</p>');
        });
    }

    public function __get($var) 
    {
        switch ($var) {
            case 'responseFactory':
            case 'streamFactory':
                return $this->{$var};
                break;
        }

        return null;
    }

    /**
     * @param string $namespace
     * 
     * @return $this
     */
    public function setDefaultNamespace(string $namespace) 
    {
        $this->defaultNamespace = $namespace;
        return $this;
    }

    /**
     * Set a custom handler for exceptions. 
     * 
     * @param callable $handler
     * 
     * @return $this
     */
    public function setExceptionHandler($handler) 
    {
        $this->exceptionHandler = $handler;
        return $this;
    }

    /**
     * Set a custom function to be called when no route is found.
     * 
     * @param callable $handler
     * 
     * @return $this
     */
    public function setNotFoundHandler($handler) 
    {
        $this->notFoundHandler = $handler;
        return $this;
    }

    /**
     * @param AdinanCenci\Router\Routing\Route $route
     * 
     * @return $this
     */
    public function addRoute(Route $route) 
    {
        $this->routeCollection->addRoute($route);
        return $this;
    }

    /**
     * @param string $methods
     * @param string Regex pattern.
     * @param callable $controller.
     * 
     * @return $this
     */
    public function add($methods, string $pattern, $controller) 
    {
        $route = $this->newRoute($methods, $pattern, $controller);
        $this->addRoute($route);
        return $this;
    }

    /** Shorthand for ::add() */
    public function get(string $pattern, $controller) 
    {
        return $this->add('get', $pattern, $controller);
    }

    /** Shorthand for ::add() */
    public function post(string $pattern, $controller) 
    {
        return $this->add('post', $pattern, $controller);
    }

    /** Shorthand for ::add() */
    public function put(string $pattern, $controller) 
    {
        return $this->add('put', $pattern, $controller);
    }

    /** Shorthand for ::add() */
    public function delete(string $pattern, $controller) 
    {
        return $this->add('delete', $pattern, $controller);
    }

    /** Shorthand for ::add() */
    public function options(string $pattern, $controller) 
    {
        return $this->add('options', $pattern, $controller);
    }

    /** Shorthand for ::add() */
    public function patch(string $pattern, $controller) 
    {
        return $this->add('patch', $pattern, $controller);
    }

    /**
     * @param AdinanCenci\Router\Routing\Route $route
     * 
     * @return $this
     */
    public function addBeforeMiddleware(Route $route) 
    {
        $this->middlewareCollection->addRoute($route);
        return $this;
    }

    public function before($methods, string $pattern, $controller) 
    {
        $middleware = $this->newRoute($methods, $pattern, $controller);
        $this->addBeforeMiddleware($middleware);
        return $this;
    }

    /**
     * Executes the route, first going through the middlewares. If no 
     * midleware generate any response, then it goes to the routes proper.
     * 
     * @param null|ServerRequestInterface 
     *   The request, if not informed the router will instantiate one itself 
     *   from global values
     * 
     * @return void
     */
    public function run(?ServerRequestInterface $request = null) : void
    {
        $request = $request
            ? $request
            : (new ServerRequestFactory())->createFromGlobals();

        $response = $this->handle($request);

        $this->sendResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface 
    {
        $path     = $this->getPath($request);
        $response = null;

        $response = $this->handleMiddlewares($request, $path);
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        $response = $this->handleRoutes($request, $path);
        return $response;
    }    

    public function getBaseUrl() : string
    {
        $scheme   = Globals::getScheme();
        $password = Globals::getPassword();
        $username = Globals::getUser();
        $host     = Globals::getHost();
        $port     = Globals::getPort();
        $path     = str_replace(Server::getServerRoot(), '', $this->baseDirectory);
        $uri      = new Uri($scheme, $username, $password, $host, $port, $path, '', '');

        return (string) $uri;
    }

    public function getUrl(string $path) : string
    {
        return $this->getBaseUrl() . ltrim($path, '/');
    }

    /**
     * @param Psr\Http\Message\ResponseInterface $response
     * 
     * @return void
     */
    protected function sendResponse(ResponseInterface $response) : void
    {
        header('HTTP/' . $response->getProtocolVersion() . ' ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase());

        foreach ($response->getHeaders() as $headerName => $value) {
            if (is_string($value)) {
                header($headerName . ': ' . $value);
                continue;
            }
            
            if (! $this->containFields($value)) {
                header($headerName . ': ' . implode(' ', $value));
                continue;
            }

            foreach ($value as $v) {
                header($headerName . ': ' . $v, false);
            }
        }

        echo $response->getBody();
    }

    /**
     * @return null|ResponseInterface
     */
    protected function handleMiddlewares(ServerRequestInterface $request, string $path) : ?ResponseInterface
    {
        if ($this->matchingMiddlewares === null) {
            $this->matchingMiddlewares = $this->getMatchingMiddlewares($request, $path);
        }

        while ($this->matchingMiddlewares) {
            $middleware = array_shift($this->matchingMiddlewares);
            $response = $middleware->callIt($request, $this, $path);

            if ($response instanceof ResponseInterface) {
                return $response;
            }
        }

        return null;
    }

    protected function handleRoutes($request, $path) : ?ResponseInterface
    {
        $route = $this->getMatchingRoute($request, $path);
        if (! $route) {
            return call_user_func_array($this->notFoundHandler, [$request, $this, $path]);
        }

        try {
            $response = $route->callIt($request, $this, $path);
        } catch (\Exception $e) {
            $response = $e;
        }

        if ($response instanceof \Exception) {
            $response = call_user_func_array($this->exceptionHandler, [$request, $this, $path, $response]);
        }

        return $response;
    }

    protected function getMatchingMiddlewares(ServerRequestInterface $request, ?string $path = null) : array
    {
        return $this->middlewareCollection->getMatchingRoutes($request, $path);
    }

    protected function getMatchingRoute(ServerRequestInterface $request, ?string $path = null) : ?Route 
    {
        $routes = $this->routeCollection->getMatchingRoutes($request, $path);

        return $routes 
            ? reset($routes) 
            : null;
    }

    /**
     * Return the path to be matched against routes and middlewares.
     * 
     * @param Psr\Http\Message\ServerRequestInterface $request
     * 
     * @return string
     */
    protected function getPath(ServerRequestInterface $request) : string 
    {
        $requestUri = ltrim($request->getUri()->getPath(), '/');
        $woulBePath = Server::getServerRoot() . $requestUri;
        return str_replace($this->baseDirectory, '', $woulBePath);
    }

    protected function newRoute($methods, $pattern, $controller) 
    {
        if (is_string($controller) || is_array($controller)) {
            $controller = $this->namespaced($controller, $this->defaultNamespace);
        }
        return new Route($methods, $pattern, $controller);
    }

    /**
     * If the callable $subjects lacks a namespace, adds $namespace.
     */
    protected function namespaced($subject, $namespace) 
    {
        if (is_array($subject) && isset($subject[0]) && is_string($subject[0])) {
            $subject[0] = $this->namespaced($subject[0]);
            return $subject;
        }

        if (! is_string($subject)) {
            return $subject;
        }

        if (substr_count($subject, '\\')) {
            return $subject;
        }

        $namespace = rtrim($namespace, '\\') . '\\';

        if (substr_count($subject, '::')) {
            $parts = explode('::', $subject);
            $subject = $parts[0];
        }

        if (function_exists($subject) || class_exists($subject)) {
            $namespaced = $subject;
        } else if (function_exists($namespace . $subject) || class_exists($namespace . $subject)) {
            $namespaced = $namespace . $subject;
        } else {
            $namespaced = $subject;
        }

        if (isset($parts)) {
            $parts[0] = $namespaced;
            $namespaced = implode('::', $parts);
        }

        return $namespaced;
    }

    protected function containFields($header) 
    {
        foreach ($header as $h) {
            if (substr_count($h, '=') || substr_count($h, ';')) {
                return true;
            }
        }

        return false;
    }
}
