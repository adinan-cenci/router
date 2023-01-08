<?php
namespace AdinanCenci\Router\Routing;

use AdinanCenci\Router\Helper\Executor;
use AdinanCenci\Router\Exception\CallbackException;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Route 
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
     * @param string|string[] $pathPatterns
     * @param mixed $controller
     */
    public function __construct($methods, $pathPatterns, $controller) 
    {
        if ($methods == '*') {
            $methods = 'GET|POST|PUT|DELETE|OPTIONS|PATCH';
        }

        $methods = is_array($methods) 
            ? array_filter($methods, 'is_string')
            : explode('|', $methods);

        $this->methods       = array_map('strtoupper', (array) $methods);
        $this->pathPatterns  = (array) $pathPatterns;
        $this->controller    = $controller;
    }

    public function doesItMatcheRequest(ServerRequestInterface $request, ?string $path = null) : bool
    {
        $method  = strtoupper($request->getMethod());
        $path    = $path !== null
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

    public function callIt(ServerRequestInterface $request, RequestHandlerInterface $handler, ?string $path = null) 
    {
        foreach ($this->pathPatterns as $pattern) {
            $attributes = [];
            preg_match($pattern, $path, $attributes);
        }

        foreach ($attributes as $attribute => $value) {
            $request = $request->withAttribute($attribute, $value);
        }

        return $this->controller instanceof MiddlewareInterface
            ? $this->callMiddleware($request, $handler)
            : $this->allTheRest($request, $handler);
    }

    protected function callMiddleware(ServerRequestInterface $request, RequestHandlerInterface $handler) 
    {
        return $this->controller->process($request, $handler);
    }

    protected function allTheRest(ServerRequestInterface $request, RequestHandlerInterface $handler) 
    {
        ob_start();

        $executor = new Executor($this->controller, ['request' => $request, 'handler' => $handler]);

        try {
            $response = $executor->callIt();
        } catch(CallbackException $e) {
            return $e;
        }

        if ($response instanceof ResponseInterface) {
            ob_end_clean();
            return $response;
        }

        $contents = is_string($response) && $response 
            ? $response 
            : ob_get_clean();

        if ($contents === '' || $contents === null) {
            return null;
        }

        $response = $handler->responseFactory->ok($contents);
        return $response;
    }
}
