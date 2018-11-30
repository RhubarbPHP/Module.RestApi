<?php

namespace Rhubarb\RestApi\Endpoints;

use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Response\Response;
use Rhubarb\RestApi\Middleware\Middleware;
use Rhubarb\RestApi\Middleware\MiddlewareProcessingTrait;

abstract class Endpoint
{
    use MiddlewareProcessingTrait;

    protected $middlewares = [];

    protected abstract function handleRequest($params, WebRequest $request);

    public final function processRequest($params, WebRequest $request)
    {
        $middlewareResponse = $this->processMiddlewares($this->middlewares, $request);

        if ($middlewareResponse){
            return $middlewareResponse;
        }

        return $this->handleRequest($params, $request);
    }

    public function with(Middleware $middleware): Endpoint
    {
        $this->middlewares[] = $middleware;

        return $this;
    }
}