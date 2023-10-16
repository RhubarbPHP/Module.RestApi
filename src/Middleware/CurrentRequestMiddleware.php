<?php

namespace Rhubarb\RestApi\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;

class CurrentRequestMiddleware
{
    private static $request;

    public function __invoke(Request $request, Response $response, callable $next): Response
    {
        if (self::$request !== $request) {
            self::$request = $request;
        }
        return $next($request, $response);
    }

    public static function getRequest(): Request
    {
        return clone self::$request;
    }
}
