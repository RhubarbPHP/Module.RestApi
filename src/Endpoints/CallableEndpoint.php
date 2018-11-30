<?php

namespace Rhubarb\RestApi\Endpoints;

use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Response\Response;

class CallableEndpoint extends Endpoint
{
    /**
     * @var callable
     */
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    protected function handleRequest($params, WebRequest $request)
    {
        $callable = $this->callable;
        return $callable($params, $request);
    }
}