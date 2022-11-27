<?php
namespace AdinanCenci\Router;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class RouteCallback extends MiddlewareCallback 
{
    protected function callMethod(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        ob_start();
        $response = call_user_func_array($this->callable, [$request, $handler]);

        if ($response instanceof ResponseInterface) {
            ob_end_clean();
            return $response;
        }

        $contents = ob_get_clean();
        $response = $handler->responseFactory->createResponse(200, '')->withBody(
            $handler->streamFactory->createStream($contents)
        );

        return $response;
    }
}
