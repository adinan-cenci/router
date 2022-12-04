<?php
namespace AdinanCenci\Router\Routes;

use AdinanCenci\Router\Helper\Executor;

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
     * @var string $pathPattern
     *   Regex pattern to be matched against the request's uri.
     */
    protected string $pathPattern;

    /**
     * @var mixed $callback
     *   An anonymous function, the name of a function, the method of a class, 
     *   an object and its method, an instance of 
     *   Psr\Http\Server\MiddlewareInterface or even the path to a file.
     */
    protected $callback;

    /**
     * @param string|string[] $methods
     * @param string $pathPattern
     * @param mixed $callback
     */
    public function __construct($methods, string $pathPattern, $callback) 
    {
        if ($methods == '*') {
            $methods = 'GET|POST|PUT|DELETE|OPTIONS|PATCH';
        }

        $methods = is_array($methods) 
            ? array_filter($methods, 'is_string')
            : explode('|', $methods);

        $this->methods      = array_map('strtoupper', (array) $methods);
        $this->pathPattern  = $pathPattern;
        $this->callback     = $callback;
    }

    public function doesItMatcheRequest(ServerRequestInterface $request, ?string $pathOverride = null) : bool
    {
        $method  = strtoupper($request->getMethod());

        $path    = $pathOverride
            ? $pathOverride 
            : $request->getUri()->getPath();

        if (! in_array($method, $this->methods)) {
            return false;
        }

        return preg_match($this->pathPattern, $path);
    }

    public function callIt(ServerRequestInterface $request, RequestHandlerInterface $handler) 
    {
        //preg_match($this->pathPattern, $path, $attributes);
        //$request = $request->withAttributes($attributes);

        return $this->callback instanceof MiddlewareInterface
            ? $this->callMiddleware($request, $handler)
            : $this->allTheRest($request, $handler);
    }    

    protected function callMiddleware(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        return $this->callback->process($request, $handler);
    }

    protected function allTheRest(ServerRequestInterface $request, RequestHandlerInterface $handler) : ?ResponseInterface
    {
        ob_start();

        $executor = new Executor($this->callback, ['request' => $request, 'handler' => $handler]);
        try {
            $response = $executor->callIt();
        } catch(\RuntimeException $e) {
            $response = $handler->responseFactory->ok($contents, 'ERROR!!! ' . $e->getMessage());
        }

        if ($response instanceof ResponseInterface) {
            ob_end_clean();
            return $response;
        }

        $contents = ob_get_clean();

        if (! $contents) {
            return null;
        }

        $response = $handler->responseFactory->ok($contents);

        return $response;
    }
}
