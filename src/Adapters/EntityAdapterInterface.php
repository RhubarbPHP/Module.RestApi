<?php

namespace Rhubarb\RestApi\Adapters;

use Slim\Http\Request;
use Slim\Http\Response;

interface EntityAdapterInterface
{
    public function list(Request $request, Response $response, $arguments = []): Response;

    public function get(Request $request, Response $response, $id): Response;

    public function post(Request $request, Response $response, $routeParams): Response;

    public function put(Request $request, Response $response, $routeParams, $id): Response;

    public function delete(Request $request, Response $response, $id): Response;
}
