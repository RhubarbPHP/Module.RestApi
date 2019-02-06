<?php

namespace Rhubarb\RestApi\ErrorHandlers;

use Slim\Http\Request;
use Slim\Http\Response;

class NotFoundHandler
{
    public function __invoke(Request $request, Response $response)
    {
        return function ($request, $response) {
            return $response->withStatus(404);
        };
    }
}
