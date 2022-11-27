<?php
namespace AdinanCenci\Router;

use AdinanCenci\Router\Helper\Server;

use AdinanCenci\Psr17\ServerRequestFactory;
use AdinanCenci\Psr17\ResponseFactory;
use AdinanCenci\Psr17\StreamFactory;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Router implements RequestHandlerInterface 
{
    /**
     * @var string $baseDirectory 
     *   Absolute path to the router's base directory. It will be used to 
     *   determine the path that will be matched against the routes.
     */
    protected string $baseDirectory;

    /** @var AdinanCenci\Router\MiddlewareCallback[] */
    protected array $middlewares = [];

    /** @var AdinanCenci\Router\MiddlewareCallback[] */
    protected ?array $matchingMiddlewares = null;

    /** @var AdinanCenci\Router\RouteCallback[] */
    protected array $routes = [];

    protected $responseFactory;

    protected $streamFactory;

    /**
     * @param string|null $baseDirectory 
     *   Path to the router's base directory. If not informed, the current 
     *   file's parent directory will be used.
     */
    public function __construct(?string $baseDirectory = null) 
    {
        $this->baseDirectory = $baseDirectory
            ? File::trailingSlash(File::forwardSlash($baseDirectory))
            : Server::getCurrentFileParentDirectory();

        $this->responseFactory = new ResponseFactory();
        $this->streamFactory = new StreamFactory();
    }

    public function __get($var) 
    {
        return $this->{$var};
    }

    /**
     * Adds a new route to the router.
     * 
     * @param string|string[] $methods
     *   The http methods ( get, post, put etc ) in the form of a "|" 
     *   separated string or an array.
     * @param string|string[] $patterns 
     *   Regex expressions to match against the URI's path.
     * @param mixed $callable 
     *   An anonymous function, the name of a function, the method of a class, 
     *   an object and its method, an instance of 
     *   Psr\Http\Server\MiddlewareInterface or even the path to a file.
     * 
     * @return self
     */
    public function add($methods = '*', $patterns, $callable) 
    {
        $this->routes[] = new RouteCallback($methods, $patterns, $callable);
        return $this;
    }

    /**
     * Adds a new middleware to the router.
     * 
     * @param string|string[] $methods
     *   The http methods ( get, post, put etc ) in the form of a "|" 
     *   separated string or an array.
     * @param string|string[] $patterns 
     *   Regex expressions to match against the URI's path.
     * @param mixed $callable 
     *   An anonymous function, the name of a function, the method of a class, 
     *   an object and its method, an instance of 
     *   Psr\Http\Server\MiddlewareInterface or even the path to a file.
     * 
     * @return self
     */
    public function addMiddleware($methods = '*', $patterns, $callable) 
    {
        $this->middlewares[] = new MiddlewareCallback($methods, $patterns, $callable);
        return $this;
    }

    /**
     * Executes the route, first going through the middlewares. If no 
     * midleware generate any response, then it goes to the routes proper.
     * 
     * @param null|ServerRequestInterface 
     *   The request, if not informed the router will instantiate one itself 
     *   from global values
     * 
     * @return void
     */
    public function run(?ServerRequestInterface $request = null) : void
    {
        $request = $request
            ? $request
            : ServerRequestFactory::createFromGlobals();

        $response = $this->handle($request);

        $this->sendResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface 
    {
        $path     = $this->getPath($request);
        $response = null;

        $response = $this->handleMiddlewares($request, $path);
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        $response = $this->handleRoutes($request, $path);
        return $response;
    }

    /**
     * @param Psr\Http\Message\ResponseInterface $response
     * 
     * @return void
     */
    protected function sendResponse(ResponseInterface $response) : void
    {
        header('HTTP/' . $response->getProtocolVersion() . ' ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase());

        foreach ($response->getHeaders() as $headerName => $value) {
            $value = is_string($value)
                ? $value 
                : implode(' ', $value);

            header($headerName . ': ' . $value);
        }

        echo $response->getBody();
    }

    /**
     * @return null|ResponseInterface
     */
    protected function handleMiddlewares(ServerRequestInterface $request, string $pathOverride) : ?ResponseInterface
    {
        $handler = $this;
        if ($this->matchingMiddlewares === null) {
            $this->matchingMiddlewares = $this->getMatchingMiddlewares($request, $pathOverride);
        }

        while ($this->matchingMiddlewares) {
            $middleware = array_shift($this->matchingMiddlewares);
            $response = $middleware->callIt($request, $handler);

            if ($response instanceof ResponseInterface) {
                return $response;
            }
        }

        return null;
    }

    protected function handleRoutes($request, $pathOverride) 
    {
        $handler = $this;
        $route = $this->getMatchingRoute($request, $pathOverride);
        if (! $route) {
            return $this->error404($request, $handler);
        }

        $response = $route->callIt($request, $handler);
        return $response;
    }

    /**
     * Will return an array of matching callbacks.
     * 
     * @param Psr\Http\Message\ServerRequestInterface $request
     * @param string $pathOverride To override the request's path.
     * 
     * @return AdinanCenci\Router\MiddlewareCallback[]
     */
    protected function getMatchingMiddlewares(ServerRequestInterface $request, ?string $pathOverride = null) : array
    {
        return array_filter($this->middlewares, function($callback) use($request, $pathOverride) 
        {
            return $callback->doesItMatcheRequest($request, $pathOverride);
        });
    }

    /**
     * Will return an array of matching callbacks.
     * 
     * @param Psr\Http\Message\ServerRequestInterface $request
     * @param string $pathOverride To override the request's path.
     * 
     * @return AdinanCenci\Router\Callback
     */
    protected function getMatchingRoute(ServerRequestInterface $request, ?string $pathOverride = null) : ?RouteCallback
    {
        $routes = array_filter($this->routes, function($callback) use($request, $pathOverride) 
        {
            return $callback->doesItMatcheRequest($request, $pathOverride);
        });

        return $routes ? reset($routes) : null;
    }

    /**
     * Return the path to be matched against routes and middlewares.
     * 
     * @param Psr\Http\Message\ServerRequestInterface $request
     * 
     * @return string
     */
    protected function getPath(ServerRequestInterface $request) : string 
    {
        $requestUri = ltrim($request->getUri()->getPath(), '/');
        $woulBePath = Server::getServerRoot() . $requestUri;
        return str_replace($this->baseDirectory, '', $woulBePath);
    }

    /**
     * @param Psr\Http\Message\ServerRequestInterface $request
     * 
     * @return Psr\Http\Message\ResponseInterface
     */
    protected function error404(ServerRequestInterface $request) : ResponseInterface
    {
        return $this->responseFactory->createResponse(404, 'Zoinks, Nothing found')
        ->withBody( $this->streamFactory->createStream('this is the home page') );
    }
}
