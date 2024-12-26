<?php

namespace AdinanCenci\Router\Routing;

use Psr\Http\Message\ServerRequestInterface;

interface RouteInterface
{
    /**
     * Checks if $request matches the route.
     *
     * @param Psr\Http\Message\ServerRequestInterface $request
     *   The request object.
     * @param string|null $path
     *   If informed, it will be used instead of the $request's path.
     *
     * @return bool
     *   True if the request matches the route.
     */
    public function doesItMatcheRequest(ServerRequestInterface $request, ?string $path = null): bool;

    /**
     * Extracts attributes from the path.
     *
     * @param Psr\Http\Message\ServerRequestInterface $request
     *   The request object.
     * @param string|null $path
     *   If informed, it will be used instead of the $request's path.
     *
     * @return string[]
     *   The attributes extracted from the request.
     */
    public function extractAttributes(ServerRequestInterface $request, ?string $path = null): array;

    /**
     * Returns the controller.
     *
     * @return mixed $controller
     *   The callback.
     */
    public function getController();
}
