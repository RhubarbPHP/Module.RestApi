<?php

namespace Rhubarb\RestApi\Middleware;

use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Response\Response;

trait MiddlewareProcessingTrait
{
    /**
     * Processes the middlewares in order and returns the response if any.
     *
     * @param $middlewares Middleware[] The middlewares to process.
     * @return null|Response
     */
    protected function processMiddlewares($middlewares, WebRequest $request): ?Response
    {
        if (count($middlewares)){
            $x = -1;

            $middlewareOutput = null;

            $runMiddleware = function($runMiddleware) use(&$x, $middlewares, $request, &$middlewareOutput){
                $x++;
                $middleware = $middlewares[$x];
                $callable = ($x + 1 == count($this->middlewares)) ? function(){} : function() use ($runMiddleware, &$middlewareOutput){
                    $runMiddleware($runMiddleware);
                };

                $output = $middleware->handleRequest($request, $callable);

                if ($output){
                    $middlewareOutput = $output;
                }
            };

            $output = $runMiddleware($runMiddleware);

            if ($output){
                $middlewareOutput = $output;
            }

            if ($middlewareOutput){
                return $middlewareOutput;
            }
        }

        return null;
    }
}