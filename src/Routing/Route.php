<?php

namespace AdinanCenci\Router\Routing;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

class Route implements RouteInterface
{
    /**
     * @var string[] $methods
     *   Http methods ( get, post, put etc )
     */
    protected array $methods;

    /**
     * @var string[] $pathPatterns
     *   Regex pattern to be matched against the request's path.
     */
    protected array $pathPatterns;

    /**
     * @var mixed $controller
     *   An anonymous function, the name of a function, the method of a class,
     *   an object and its method, an instance of
     *   Psr\Http\Server\MiddlewareInterface or even the path to a file.
     */
    protected $controller;

    /**
     * @param string|string[] $methods
     *   HTTP methods.
     * @param string|string[] $pathPatterns
     *   Regex patterns.
     * @param mixed $controller
     *   The callback.
     */
    public function __construct($methods, $pathPatterns, $controller)
    {
        if ($methods == '*') {
            $methods = 'GET|POST|PUT|DELETE|OPTIONS|PATCH';
        }

        $methods = is_array($methods)
            ? array_filter($methods, 'is_string')
            : explode('|', $methods);

        $this->methods      = array_map('strtoupper', (array) $methods);
        $this->pathPatterns = (array) $pathPatterns;
        $this->controller   = $controller;
    }

    /**
     * {@inheritdoc}
     */
    public function doesItMatcheRequest(ServerRequestInterface $request, ?string $path = null): bool
    {
        $method = strtoupper($request->getMethod());
        $path   = $path !== null
            ? $path
            : $request->getUri()->getPath();

        if (! in_array($method, $this->methods)) {
            return false;
        }

        foreach ($this->pathPatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function extractAttributes(ServerRequestInterface $request, ?string $path = null): array
    {
        $path = $path !== null
            ? $path
            : $request->getUri()->getPath();

        foreach ($this->pathPatterns as $pattern) {
            $attributes = [];
            preg_match($pattern, $path, $attributes);
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getController()
    {
        return $this->controller;
    }
}
