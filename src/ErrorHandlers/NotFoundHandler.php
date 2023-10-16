<?php

namespace Rhubarb\RestApi\ErrorHandlers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;

class NotFoundHandler
{
    public function __invoke(Request $request, Response $response): Response
    {
        return $response->withStatus(404);
    }
}
