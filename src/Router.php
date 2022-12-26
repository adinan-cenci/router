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

class Router implements RequestHandlerInterface 
{
    /**
     * @var string $baseDirectory 
     *   Absolute path to the router's base directory. It will be used to 
     *   determine the path that will be matched against the routes.
     */
    protected string $baseDirectory;

    /** @var AdinanCenci\Router\Routing\RouteCollection */
    protected $middlewareCollection;

    /** @var AdinanCenci\Router\Routing\RouteCollection */
    protected $routeCollection;

    /** @var AdinanCenci\Router\Routing\Route[] */
    protected ?array $matchingMiddlewares = null;

    /** @var AdinanCenci\Psr17\ResponseFactory */
    protected $responseFactory;

    /** @var AdinanCenci\Psr17\StreamFactory */
    protected $streamFactory;

    protected $exceptionHandler;

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

        $this->exceptionHandler = function($handler, $exception) 
        {
            return $handler->responseFactory->internalServerError('<h1>Error</h1>' . $exception->getMessage());
        };

        $this->notFoundHandler = function($request, $handler, $pathOverride) 
        {
            return $handler->responseFactory->notFound('404 Nothing found related to "' . $pathOverride . '"');
        };
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

    public function setExceptionHandler($handler) 
    {
        $this->exceptionHandler = $handler;
        return $this;
    }

    public function setNotFoundHandler($handler) 
    {
        $this->notFoundHandler = $handler;
        return $this;
    }

    public function addRoute(Route $route, ?string $routeName = null) 
    {
        $this->routeCollection->addRoute($route, $routeName);
        return $this;
    }

    public function add($methods, string $pattern, $callable, ?string $routeName = null) 
    {
        $route = new Route($methods, $pattern, $callable);
        $this->addRoute($route, $routeName);
        return $this;
    }

    public function addMiddleware(Route $route) 
    {
        $this->middlewareCollection->addRoute($route, null);
        return $this;
    }

    public function middleware($methods, string $pattern, $callable) 
    {
        $middleware = new Route($methods, $pattern, $callable);
        $this->addMiddleware($middleware);
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

    /**
     * @param Psr\Http\Message\ResponseInterface $response
     * 
     * @return void
     */
    protected function sendResponse(ResponseInterface $response) : void
    {
        header('HTTP/' . $response->getProtocolVersion() . ' ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase());

        foreach ($response->getHeaders() as $headerName => $value) {
            $value = is_string($value)
                ? $value 
                : implode(' ', $value);

            header($headerName . ': ' . $value);
        }

        echo $response->getBody();
    }

    /**
     * @return null|ResponseInterface
     */
    protected function handleMiddlewares(ServerRequestInterface $request, string $pathOverride) : ?ResponseInterface
    {
        $handler = $this;
        if ($this->matchingMiddlewares === null) {
            $this->matchingMiddlewares = $this->getMatchingMiddlewares($request, $pathOverride);
        }

        while ($this->matchingMiddlewares) {
            $middleware = array_shift($this->matchingMiddlewares);
            $response = $middleware->callIt($request, $handler);

            if ($response instanceof ResponseInterface) {
                return $response;
            }
        }

        return null;
    }

    protected function handleRoutes($request, $pathOverride) 
    {
        $route = $this->getMatchingRoute($request, $pathOverride);
        if (! $route) {
            return $this->error404($request, $this, $pathOverride);
        }

        try {
            $response = $route->callIt($request, $this);
        } catch (CallbackException $e) {
            $response = $e;
        }

        if ($response instanceof CallbackException) {
            return $this->handleException($response);
        }

        return $response;
    }

    protected function handleException(\Exception $exception) 
    {
        $function = $this->exceptionHandler;
        return $function($this, $exception);
    }

    protected function getMatchingRoute(ServerRequestInterface $request, ?string $pathOverride = null) : ?Route 
    {
        $routes = $this->routeCollection->getMatchingRoutes($request, $pathOverride);

        return $routes 
            ? reset($routes) 
            : null;
    }

    protected function getMatchingMiddlewares(ServerRequestInterface $request, ?string $pathOverride = null) : array
    {
        return $this->middlewareCollection->getMatchingRoutes($request, $pathOverride);
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

    public function getUrl($path) 
    {
        return $this->getBaseUrl() . ltrim($path, '/');
    }

    /**
     * @param Psr\Http\Message\ServerRequestInterface $request
     * 
     * @return Psr\Http\Message\ResponseInterface
     */
    protected function error404(ServerRequestInterface $request, $handler, $pathOverride = null) : ResponseInterface
    {
        return call_user_func_array($this->notFoundHandler, [$request, $handler, $pathOverride]);
    }
}
