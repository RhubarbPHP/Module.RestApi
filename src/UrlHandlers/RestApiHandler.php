<?php

namespace Rhubarb\RestApi\UrlHandlers;

use Rhubarb\RestApi\Exceptions\ResourceNotFoundException;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Response\JsonResponse;
use Rhubarb\Crown\Response\NotFoundResponse;
use Rhubarb\Crown\Response\Response;
use Rhubarb\Crown\UrlHandlers\UrlHandler;
use Rhubarb\RestApi\Middleware\Middleware;

class RestApiHandler extends UrlHandler
{
    private $routes = [
        "get" => [],
        "post" => [],
        "put" => [],
        "delete" => []
    ];

    /**
     * @var Middleware[]
     */
    private $middlewares = [];

    public function addMiddleware(Middleware $middleware)
    {
        $this->middlewares[] = $middleware;
    }

    public function get($endPoint, $adapter)
    {
        $this->routes["get"][$endPoint] = $adapter;

        krsort($this->routes["get"]);
    }

    public function post($endPoint, $adapter)
    {
        $this->routes["post"][$endPoint] = $adapter;

        krsort($this->routes["post"]);
    }

    public function put($endPoint, $adapter)
    {
        $this->routes["put"][$endPoint] = $adapter;

        krsort($this->routes["put"]);
    }

    public function delete($endPoint, $adapter)
    {
        $this->routes["delete"][$endPoint] = $adapter;

        krsort($this->routes["delete"]);
    }

    /**
     * Return the response if appropriate or false if no response could be generated.
     *
     * @param mixed $request
     * @return bool|Response
     */
    protected function generateResponseForRequest($request = null)
    {
        if (count($this->middlewares)){
            $x = -1;

            $middlewareOutput = null;

            $runMiddleware = function($runMiddleware) use(&$x, $request, &$middlewareOutput){
                $x++;
                $middleware = $this->middlewares[$x];
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

        /**
         * @var WebRequest $request;
         */

        $method = strtolower($request->server("REQUEST_METHOD"));

        $endPoints = $this->routes[$method];

        $remainingUrl = str_replace($this->matchingUrl, "", $request->urlPath);
        $jsonResponse = new JsonResponse();
        $responseBody = new \stdClass();

        foreach($endPoints as $endPoint => $callable){
            $endPoint = preg_replace("|/:([^/]+)|", "/(?<\\1>[^/]+)",$endPoint);

            if (preg_match('|'.$endPoint.'|', $remainingUrl, $matches)){

                try {
                    $responseBody = $callable($matches, $request);
                } catch (ResourceNotFoundException $er){
                    $response = new NotFoundResponse();
                    $response->setContent("The resource could not be located.");
                    return $response;
                } catch (\Throwable $er){
                    $response = new Response();
                    $response->setResponseCode(500);
                    $response->setResponseMessage("An internal error occurred.");

                    return $response;
                }

                break;
            }
        }

        $jsonResponse->setContent($responseBody);

        return $jsonResponse;
    }
}