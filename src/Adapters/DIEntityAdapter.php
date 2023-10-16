<?php

namespace Rhubarb\RestApi\Adapters;

use Rhubarb\Crown\DependencyInjection\Container;
use Slim\Http\Response;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class DIEntityAdapter implements EntityAdapterInterface
{
    /** @var EntityAdapterInterface */
    private $entityAdapter;

    public function __construct()
    {
        $this->entityAdapter = Container::instance(static::class);
    }

    final public function list(Request $request, Response $response): Response
    {
        return $this->entityAdapter->list(...func_get_args());
    }

    final public function get(Request $request, Response $response, $id): Response
    {
        return $this->entityAdapter->get(...func_get_args());
    }

    final public function post(Request $request, Response $response): Response
    {
        return $this->entityAdapter->post(...func_get_args());
    }

    final public function put(Request $request, Response $response, $id): Response
    {
        return $this->entityAdapter->put(...func_get_args());
    }

    final public function delete(Request $request, Response $response, $id): Response
    {
        return $this->entityAdapter->delete(...func_get_args());
    }
}
