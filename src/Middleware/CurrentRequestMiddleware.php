<?php

namespace Rhubarb\RestApi\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\MiddlewareDispatcher;

class CurrentRequestMiddleware
{
    private static $request;

    public function __invoke(Request $request, MiddlewareDispatcher $middlewareDispatcher): Response
    {
        $response = $middlewareDispatcher->handle($request);
        if (self::$request !== $request) {
            self::$request = $request;
        }
        return  $response;
    }

    public static function getRequest(): Request
    {
        return clone self::$request;
    }
}
