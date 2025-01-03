<?php

namespace AdinanCenci\Router\Routing;

use Psr\Http\Message\ServerRequestInterface;

interface RouteCollectionInterface
{
    /**
     * Adds a route to the collection.
     *
     * @param AdinanCenci\Router\Routes\RouteInterface $route
     *   Route object.
     * @param string|null $routeName
     *   An optional name for the route.
     *
     * @return AdinanCenci\Router\Routing\RouteCollection
     *   This.
     */
    public function addRoute(Route $route, ?string $routeName = null);

    /**
     * Adds a new route to the collection.
     *
     * @param string|string[] $methods
     *   The http methods ( get, post, put etc ) in the form of a "|"
     *   separated string or an array.
     * @param string|string[] $pattern
     *   Regex expressions to match against the URI's path.
     * @param mixed $callable
     *   An anonymous function, the name of a function, the method of a class,
     *   an object and its method, an instance of
     *   Psr\Http\Server\MiddlewareInterface or even the path to a file.
     * @param string $routeName
     *   An unique string to identify the router, optional.
     *
     * @return AdinanCenci\Router\Routing\RouteCollection
     *   This.
     */
    public function add($methods, $pattern, $callable, $routeName = null);

    /**
     * Returns an array of matching routes.
     *
     * @param Psr\Http\Message\ServerRequestInterface $request
     *   The request object.
     * @param string|null $path To override the request's path.
     *   If informed, it will be used instead of the $request's path.
     *
     * @return AdinanCenci\Router\Routes\RouteInterface[]
     *   Matching routes.
     */
    public function getMatchingRoutes(ServerRequestInterface $request, ?string $path = null): array;
}
