<?php
namespace AdinanCenci\Router;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class MiddlewareCallback 
{
    /**
     * @var string[] $methods 
     *   Http methods ( get, post, put etc )
     */
    protected array $methods;

    /**
     * @var string[] $pathPatterns
     *   Regex patterns to be matched against the request's uri.
     */
    protected array $pathPatterns;

    /**
     * @var mixed $callable
     *   An anonymous function, the name of a function, the method of a class, 
     *   an object and its method, an instance of 
     *   Psr\Http\Server\MiddlewareInterface or even the path to a file.
     */
    protected $callable;

    public function __construct(array $methods, array $pathPatterns, $callable) 
    {
        $this->methods      = array_map('strtoupper', $methods);
        $this->pathPatterns = $pathPatterns;
        $this->callable     = $callable;
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

        foreach ($this->pathPatterns as $regexPattern) {
            if (preg_match($regexPattern, $path)) {
                return true;
            }
        }

        return false;
    }

    public function callIt(ServerRequestInterface $request, RequestHandlerInterface $handler) 
    {
        if ($this->callable instanceof MiddlewareInterface) {
            return $this->callMiddleware($request, $handler);            
        }

        return $this->callMethod($request, $handler);
    }

    protected function callMiddleware(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        return $this->callable->process($request, $handler);
    }

    protected function callMethod(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        return call_user_func_array($this->callable, [$request, $handler]);
    }
}
