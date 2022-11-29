<?php
namespace AdinanCenci\Router;

use AdinanCenci\Router\Helper\Server;
use AdinanCenci\Router\Routes\RouteCollection;
use AdinanCenci\Router\Routes\Route;

use AdinanCenci\Psr17\ServerRequestFactory;
use AdinanCenci\Psr17\ResponseFactory;
use AdinanCenci\Psr17\StreamFactory;
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

    /** @var AdinanCenci\Router\Routes\RouteCollection */
    protected $middlewareCollection;

    /** @var AdinanCenci\Router\Routes\RouteCollection */
    protected $routeCollection;

    /** @var AdinanCenci\Router\Middleware[] */
    protected ?array $matchingMiddlewares = null;

    protected $responseFactory;

    protected $streamFactory;

    /**
     * @param string|null $baseDirectory
     *   Absolute path. If not informed, the current file's parent directory 
     *   will be used.
     */
    public function __construct(?string $baseDirectory = null) 
    {
        $this->baseDirectory = $baseDirectory
            ? File::trailingSlash(File::forwardSlash($baseDirectory))
            : Server::getCurrentFileParentDirectory();

        $this->responseFactory = new ResponseFactory();
        $this->streamFactory = new StreamFactory();

        $this->middlewareCollection = new RouteCollection();
        $this->routeCollection = new RouteCollection();
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

    public function addRoute(Route $route, $routeName = null) 
    {
        $this->routeCollection->addRoute($route, $routeName);
        return $this;
    }

    public function add($methods, string $pattern, $callable, $routeName = null) 
    {
        $this->addRoute(new Route($methods, $pattern, $callable), $routeName);
        return $this;
    }

    public function addMiddleware(Route $route) 
    {
        $this->middlewareCollection->addRoute($route, null);
        return $this;
    }

    public function middleware($methods, string $pattern, $callable) 
    {
        $this->addMiddleware(new Route($methods, $pattern, $callable));
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
            : ServerRequestFactory::createFromGlobals();

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
        $handler = $this;
        $route = $this->getMatchingRoute($request, $pathOverride);
        if (! $route) {
            return $this->error404($request, $handler);
        }

        $response = $route->callIt($request, $handler);
        return $response;
    }

    protected function getMatchingRoute(ServerRequestInterface $request, ?string $pathOverride = null) : ?Route 
    {
        $routes = $this->routeCollection->getMatchingRoutes($request, $pathOverride);
        return $routes ? reset($routes) : null;
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

    /**
     * @param Psr\Http\Message\ServerRequestInterface $request
     * 
     * @return Psr\Http\Message\ResponseInterface
     */
    protected function error404(ServerRequestInterface $request) : ResponseInterface
    {
        return $this->responseFactory->createResponse(404, 'Zoinks, Nothing found')
        ->withBody( $this->streamFactory->createStream('Nothing found') );
    }
}
