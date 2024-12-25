<?php

namespace AdinanCenci\Router\Routing;

use Psr\Http\Message\ServerRequestInterface;

class RouteCollection implements RouteCollectionInterface
{
    /**
     * @var AdinanCenci\Router\Routing\RouteInterface[] $routes
     *   Array of route objects.
     */
    protected array $routes = [];

    /**
     * {@inheritdoc}
     */
    public function addRoute(Route $route, ?string $routeName = null)
    {
        if ($routeName) {
            $this->routes[ $routeName ] = $route;
        } else {
            $this->routes[] = $route;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function add($methods, $pattern, $callable, $routeName = null)
    {
        $this->addRoute(new Route($methods, $pattern, $callable), $routeName);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchingRoutes(ServerRequestInterface $request, ?string $path = null): array
    {
        return array_filter($this->routes, function ($route) use ($request, $path) {
            return $route->doesItMatcheRequest($request, $path);
        });
    }
}
