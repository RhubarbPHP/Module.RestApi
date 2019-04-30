<?php

namespace Rhubarb\RestApi\Middleware;

use Slim\Http\Request;
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
