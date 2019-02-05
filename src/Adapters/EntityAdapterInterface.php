<?php

namespace Rhubarb\RestApi\Adapters;

use Slim\Http\Request;
use Slim\Http\Response;

interface EntityAdapterInterface
{
    public static function list(Request $request, Response $response): Response;

    public static function get($id, Request $request, Response $response): Response;

    public static function post(Request $request, Response $response): Response;

    public static function put($id, Request $request, Response $response): Response;

    public static function delete($id, Request $request, Response $response): Response;
}
