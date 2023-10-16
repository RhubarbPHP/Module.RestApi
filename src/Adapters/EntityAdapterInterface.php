<?php

namespace Rhubarb\RestApi\Adapters;

use Slim\Http\Response;
use Psr\Http\Message\ServerRequestInterface as Request;

interface EntityAdapterInterface
{
    public function list(Request $request, Response $response): Response;

    public function get(Request $request, Response $response, $id): Response;

    public function post(Request $request, Response $response): Response;

    public function put(Request $request, Response $response, $id): Response;

    public function delete(Request $request, Response $response, $id): Response;
}
