<?php
namespace AdinanCenci\Router\Routing;

use Psr\Http\Message\ServerRequestInterface;

class RouteCollection 
{
    protected $routes = [];

    /**
     * @param AdinanCenci\Router\Routes\Route $route
     * @param string $routeName
     * 
     * @return $this
     */
    public function addRoute(Route $route, $routeName = null) 
    {
        if ($routeName) {
            $this->routes[ $routeName ] = $route;
        } else {
            $this->routes[] = $route;
        }

        return $this;
    }

    /**
     * Adds a new route to the collection.
     * 
     * @param string|string[] $methods
     *   The http methods ( get, post, put etc ) in the form of a "|" 
     *   separated string or an array.
     * @param string $pattern 
     *   Regex expressions to match against the URI's path.
     * @param mixed $callable 
     *   An anonymous function, the name of a function, the method of a class, 
     *   an object and its method, an instance of 
     *   Psr\Http\Server\MiddlewareInterface or even the path to a file.
     * @param string $routeName
     *   An unique string to identify the router, optional.
     * 
     * @return self
     */
    public function add($methods, string $pattern, $callable, $routeName = null) 
    {
        $this->addRoute(new Route($methods, $pattern, $callable), $routeName);
        return $this;
    }

    /**
     * Will return an array of matching callbacks.
     * 
     * @param Psr\Http\Message\ServerRequestInterface $request
     * @param string|null $path To override the request's path.
     * 
     * @return AdinanCenci\Router\Routes\Route[]
     */
    public function getMatchingRoutes(ServerRequestInterface $request, ?string $path = null) : array
    {
        return array_filter($this->routes, function($route) use($request, $path) 
        {
            return $route->doesItMatcheRequest($request, $path);
        });
    }
}
