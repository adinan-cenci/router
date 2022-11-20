<?php
namespace AdinanCenci\Router;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Callback 
{
    protected array $methods;
    protected array $pathPatterns;
    protected $callable;

    public function __construct(array $methods, array $pathPatterns, $callable) 
    {
        $this->methods = $methods;
        $this->pathPatterns = $pathPatterns;
        $this->callable = $callable;
    }

    public function matchesRequest(ServerRequestInterface $request, ?string $pathOverride = null) : bool
    {
        $method  = $request->getMethod();
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

    public function callIt(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        if ($this->callable instanceof MiddlewareInterface) {
            $this->callable->process($request, $handler);
        }

        return call_user_func_array($this->callable, [$request, $handler]);
    }
}
