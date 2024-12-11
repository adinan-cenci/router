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

    /**
     * @var AdinanCenci\Router\Routing\RouteCollection
     *   Collection of routes.
     */
    protected RouteCollection $middlewareCollection;

    /**
     * @var AdinanCenci\Router\Routing\RouteCollection
     *   Collection of middlewares.
     */
    protected RouteCollection $routeCollection;

    /**
     * @var AdinanCenci\Router\Routing\Route[]
     *   A list of middlewares that match the current request.
     */
    protected ?array $matchingMiddlewares = null;

    /**
     * @var AdinanCenci\Psr17\ResponseFactory
     *   A response factory object.
     */
    protected ResponseFactory $responseFactory;

    /**
     * @var AdinanCenci\Psr17\StreamFactory
     *   A stream factory.
     */
    protected StreamFactory $streamFactory;

    /**
     * @var callable $exceptionHandler
     *   A function to deal with exceptions.
     *   The callback will receive 4 parameters:
     *   (ServerRequestInterface $request, RequestHandlerInterface $handler, string $path, \Exception $exception)
     */
    protected $exceptionHandler;

    /**
     * @var callable $exceptionHandler
     *   A function to be called when no route is found.
     *   The callback function will receive 3 parameters:
     *   (ServerRequestInterface $request, RequestHandlerInterface $handler, string $path)
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
        $this->setExceptionHandler(function ($request, $handler, $path, $exception) {
            return $handler
                ->responseFactory
                ->internalServerError('<h1>Error 500</h1><p>' . $exception->getMessage() . '</p>');
        });

        // Default handler.
        $this->setNotFoundHandler(function ($request, $handler, $path) {
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
     * Set the default namespace.
     *
     * So we don't need to repeat ourselves over and over.
     *
     * @param string $namespace
     *   The namespace.
     *
     * @return AdinanCenci\Router\Router
     *   This.
     */
    public function setDefaultNamespace(string $namespace)
    {
        $this->defaultNamespace = $namespace;
        return $this;
    }

    /**
     * Sets a function to handle exceptions.
     *
     * @param callable $handler
     *   The callback.
     *
     * @return AdinanCenci\Router\Router
     *   This.
     */
    public function setExceptionHandler($handler)
    {
        $this->exceptionHandler = $handler;
        return $this;
    }

    /**
     * Sets a function to handle exceptions.
     *
     * @param callable $handler
     *   The callback.
     *
     * @return AdinanCenci\Router\Router
     *   This.
     */
    public function setNotFoundHandler($handler)
    {
        $this->notFoundHandler = $handler;
        return $this;
    }

    /**
     * Adds a route to the collection.
     *
     * @param AdinanCenci\Router\Routing\Route $route
     *   The route.
     *
     * @return AdinanCenci\Router\Router
     *   This.
     */
    public function addRoute(Route $route)
    {
        $this->routeCollection->addRoute($route);
        return $this;
    }

    /**
     * Creates and add a route to the collection.
     *
     * @param string|string[] $methods
     *   HTTP methods.
     * @param string|string[] $pattern
     *   Regex patterns.
     * @param mixed $controller
     *   The callback.
     *
     * @return AdinanCenci\Router\Router
     *   This.
     */
    public function add($methods, $pattern, $controller)
    {
        $route = $this->newRoute($methods, $pattern, $controller);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Shorthand for ::add()
     *
     * @param string|string[] $pattern
     *   Regex patterns.
     * @param mixed $controller
     *   The callback.
     *
     * @return AdinanCenci\Router\Router
     *   This.
     */
    public function get($pattern, $controller)
    {
        return $this->add('get', $pattern, $controller);
    }

    /**
     * Shorthand for ::add()
     *
     * @param string|string[] $pattern
     *   Regex patterns.
     * @param mixed $controller
     *   The callback.
     *
     * @return AdinanCenci\Router\Router
     *   This.
     */
    public function post($pattern, $controller)
    {
        return $this->add('post', $pattern, $controller);
    }

    /**
     * Shorthand for ::add()
     *
     * @param string|string[] $pattern
     *   Regex patterns.
     * @param mixed $controller
     *   The callback.
     *
     * @return AdinanCenci\Router\Router
     *   This.
     */
    public function put($pattern, $controller)
    {
        return $this->add('put', $pattern, $controller);
    }

    /**
     * Shorthand for ::add()
     *
     * @param string|string[] $pattern
     *   Regex patterns.
     * @param mixed $controller
     *   The callback.
     *
     * @return AdinanCenci\Router\Router
     *   This.
     */
    public function delete($pattern, $controller)
    {
        return $this->add('delete', $pattern, $controller);
    }

    /**
     * Shorthand for ::add()
     *
     * @param string|string[] $pattern
     *   Regex patterns.
     * @param mixed $controller
     *   The callback.
     *
     * @return AdinanCenci\Router\Router
     *   This.
     */
    public function options($pattern, $controller)
    {
        return $this->add('options', $pattern, $controller);
    }

    /**
     * Shorthand for ::add()
     *
     * @param string|string[] $pattern
     *   Regex patterns.
     * @param mixed $controller
     *   The callback.
     *
     * @return AdinanCenci\Router\Router
     *   This.
     */
    public function patch($pattern, $controller)
    {
        return $this->add('patch', $pattern, $controller);
    }

    /**
     * Adds a route to the before middleware collection.
     *
     * @param AdinanCenci\Router\Routing\Route $route
     *   The route.
     *
     * @return AdinanCenci\Router\Router
     *   This.
     */
    public function addBeforeMiddleware(Route $route)
    {
        $this->middlewareCollection->addRoute($route);
        return $this;
    }

    /**
     * Creates and add a route to the before middleware collection.
     *
     * @param string|string[] $methods
     *   HTTP methods.
     * @param string|string[] $pattern
     *   Regex patterns.
     * @param mixed $controller
     *   The callback.
     *
     * @return AdinanCenci\Router\Router
     *   This.
     */
    public function before($methods, $pattern, $controller)
    {
        $middleware = $this->newRoute($methods, $pattern, $controller);
        $this->addBeforeMiddleware($middleware);
        return $this;
    }

    /**
     * Executes the router.
     *
     * First going through the middlewares. If no midleware generate any
     * response, then it goes to the routes proper.
     *
     * @param null|Psr\Http\Message\ServerRequestInterface
     *   The request, if not informed the router will instantiate one itself
     *   from global values
     *
     * @return void
     */
    public function run(?ServerRequestInterface $request = null): void
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
    public function handle(ServerRequestInterface $request): ResponseInterface
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

    /**
     * Returns the base URL.
     *
     * To the directory where the router is running from.
     *
     * @return string
     *   Absolute URL.
     */
    public function getBaseUrl(): string
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

    /**
     * Generates an absolute URL from a relative one.
     *
     * @param string $path
     *   Relative URL.
     *
     * @return string
     *   Absolute URL
     */
    public function getUrl(string $path): string
    {
        return $this->getBaseUrl() . '/' . ltrim($path, '/');
    }

    /**
     * Outputs a response.
     *
     * @param Psr\Http\Message\ResponseInterface $response
     *   The response object.
     *
     * @return void
     */
    protected function sendResponse(ResponseInterface $response): void
    {
        header('HTTP/' . $response->getProtocolVersion() . ' ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase());

        foreach ($response->getHeaders() as $headerName => $value) {
            if (is_string($value)) {
                header($headerName . ': ' . $value);
                continue;
            }

            // Renders all values into a single string.
            if (! $this->containFields($value)) {
                header($headerName . ': ' . implode(' ', $value));
                continue;
            }

            // Renders values individually.
            foreach ($value as $v) {
                header($headerName . ': ' . $v, false);
            }
        }

        echo $response->getBody();
    }

    /**
     * Handles the middlewares.
     *
     * @param Psr\Http\Message\ServerRequestInterface
     *   Request object.
     * @param string|null $path To override the request's path.
     *   If informed, it will be used instead of the $request's path.
     *
     * @return null|Psr\Http\Message\ResponseInterface
     *   Response object.
     */
    protected function handleMiddlewares(ServerRequestInterface $request, ?string $path = null): ?ResponseInterface
    {
        $path = $path !== null
            ? $path
            : $request->getUri()->getPath();

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

    /**
     * Handles the routes proper.
     *
     * @param Psr\Http\Message\ServerRequestInterface
     *   Request object.
     * @param string|null $path To override the request's path.
     *   If informed, it will be used instead of the $request's path.
     *
     * @return null|Psr\Http\Message\ResponseInterface
     *   Response object.
     */
    protected function handleRoutes(ServerRequestInterface $request, ?string $path = null): ?ResponseInterface
    {
        $path = $path !== null
            ? $path
            : $request->getUri()->getPath();

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

    /**
     * Returns middlewares that match the request.
     *
     * @param Psr\Http\Message\ServerRequestInterface
     *   Request object.
     * @param string|null $path To override the request's path.
     *   If informed, it will be used instead of the $request's path.
     *
     * @return AdinanCenci\Router\Routing\Route[]
     *   Matching routes.
     */
    protected function getMatchingMiddlewares(ServerRequestInterface $request, ?string $path = null): array
    {
        return $this->middlewareCollection->getMatchingRoutes($request, $path);
    }

    /**
     * Returns the first route that matches the request.
     *
     * @param Psr\Http\Message\ServerRequestInterface
     *   Request object.
     * @param string|null $path To override the request's path.
     *   If informed, it will be used instead of the $request's path.
     *
     * @return null|AdinanCenci\Router\Routing\Route
     *   Matching route.
     */
    protected function getMatchingRoute(ServerRequestInterface $request, ?string $path = null): ?Route
    {
        $routes = $this->routeCollection->getMatchingRoutes($request, $path);

        return $routes
            ? reset($routes)
            : null;
    }

    /**
     * Return the path to be matched against routes and middlewares.
     *
     * It takes consideration any directory the router may be inside of.
     *
     * @param Psr\Http\Message\ServerRequestInterface $request
     *   Request object.
     *
     * @return string
     *   Relative URL.
     */
    protected function getPath(ServerRequestInterface $request): string
    {
        $requestUri = ltrim($request->getUri()->getPath(), '/');
        $woulBePath = Server::getServerRoot() . $requestUri;
        return str_replace($this->baseDirectory, '', $woulBePath);
    }

    /**
     * Instantiate a new route object.
     *
     * @param string|string[] $methods
     *   HTTP methods.
     * @param string|string[] $pattern
     *   Regex patterns.
     * @param mixed $controller
     *   The callback.
     *
     * @return AdinanCenci\Router\Routing\Route
     *   Route.
     */
    protected function newRoute($methods, $pattern, $controller)
    {
        if (is_string($controller) || is_array($controller)) {
            $controller = $this->namespaced($controller, $this->defaultNamespace);
        }
        return new Route($methods, $pattern, $controller);
    }

    /**
     * If the callable $subjects lacks a namespace, adds $namespace.
     *
     * @param callable $subject
     *   The callable.
     * @param string $namespace
     *   The namespace to be prefixed to $subject.
     *
     * @return callable
     *   A namespaced $subject.
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
        } elseif (function_exists($namespace . $subject) || class_exists($namespace . $subject)) {
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

    /**
     * Checks if the header is composed.
     *
     * @param string[] $headerValues
     *   Header values.
     *
     * @return bool
     */
    protected function containFields(array $headerValues): bool
    {
        foreach ($headerValues as $h) {
            if (substr_count($h, '=') || substr_count($h, ';')) {
                return true;
            }
        }

        return false;
    }
}
