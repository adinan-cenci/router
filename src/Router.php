<?php
namespace AdinanCenci\Router;

use AdinanCenci\Router\Helper\Server;

use AdinanCenci\Psr17\ServerRequestFactory;
use AdinanCenci\Psr17\ResponseFactory;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Router implements RequestHandlerInterface 
{
    protected string $baseDirectory;

    protected array $middlewares = [];

    protected array $routes = [];

    protected ?array $matchingMiddlewares = null;

    /**
     * @param string|null $baseDirectory Absolute path to the router's base directory.
     * It will be used to determine the path that will be matched against the routes.
     * If not informed, the current file's parent directory will be used.
     */
    public function __construct(?string $baseDirectory = null) 
    {
        $this->baseDirectory = $baseDirectory
            ? File::trailingSlash(File::forwardSlash($baseDirectory))
            : Server::getCurrentFileParentDirectory();
    }

    public function addMiddleware($methods, $patterns, $callable) 
    {
        $this->middlewares[] = new Callback($methods, $patterns, $callable);
    }

    public function run(?ServerRequestInterface $request = null) 
    {
        $request = $request
            ? $request
            : ServerRequestFactory::createFromGlobals();

        return $this->handle($request);
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface 
    {
        $path = $this->getPath($request);
        $response = null;

        if ($this->matchingMiddlewares === null) {
            $this->matchingMiddlewares = $this->getMatchingMiddlewares($request, $path);
        }

        while ($this->matchingMiddlewares) {
            $middleware = array_shift($this->matchingMiddlewares);
            $response = $middleware->callIt($request, $this);
        }

        if ($response instanceof ResponseInterface) {
            return $response;
        }

        $route = $this->getMatchingRoute($request, $path);

        if ($route) {
            $response = $route->call($request, $this);
        }

        if ($response instanceof ResponseInterface) {
            return $response;
        }

        return $this->error404();
    }

    /**
     * Will return an array of matching callbacks.
     * 
     * @param Psr\Http\Message\ServerRequestInterface $request
     * @param string $path To override the request's path.
     * 
     * @return AdinanCenci\Router\Callback[]
     */
    protected function getMatchingMiddlewares(ServerRequestInterface $request, ?string $path = null) : array
    {
        return array_filter($this->middlewares, function($callback) use($request, $path) 
        {
            return $callback->matchesRequest($request, $path);
        });
    }

    protected function getMatchingRoute(ServerRequestInterface $request, ?string $path = null) : ?Callback
    {
        $routes = array_filter($this->routes, function($callback) use($request, $path) 
        {
            return $callback->matchesRequest($request, $path);
        });
        return $routes ? reset($routes) : null;
    }

    protected function getPath(ServerRequestInterface $request) : string 
    {
        $requestUri = ltrim($request->getUri()->getPath(), '/');
        $woulBePath = Server::getServerRoot() . $requestUri;
        return str_replace($this->baseDirectory, '', $woulBePath);
    }

    protected function error404() 
    {
        return (new ResponseFactory())->createResponse(404, 'Nothing found');
    }
}
