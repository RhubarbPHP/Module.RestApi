<?php

namespace Rhubarb\RestApi\Middleware;

use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Response\Response;

abstract class Middleware
{
    public abstract function handleRequest(WebRequest $request, callable $next): ?Response;
}