<?php

namespace Rhubarb\RestApi\UrlHandlers;

use Rhubarb\Crown\Response\HtmlResponse;
use Rhubarb\RestApi\Endpoints\CallableEndpoint;
use Rhubarb\RestApi\Endpoints\Endpoint;
use Rhubarb\RestApi\Exceptions\ResourceNotFoundException;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Response\JsonResponse;
use Rhubarb\Crown\Response\NotFoundResponse;
use Rhubarb\Crown\Response\Response;
use Rhubarb\Crown\UrlHandlers\UrlHandler;
use Rhubarb\RestApi\Middleware\Middleware;
use Rhubarb\RestApi\Middleware\MiddlewareProcessingTrait;

class RestApiHandler extends UrlHandler
{
    use MiddlewareProcessingTrait;

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

    /**
     * Registers a route
     *
     * @param $type string get, post, put or delete
     * @param $route string
     * @param $endpoint callable|Endpoint
     * @return Endpoint
     */
    protected function registerRoute($type, $route, $endpoint): Endpoint
    {
        if (is_callable($endpoint)){
            $endpoint = new CallableEndpoint($endpoint);
        }

        $this->routes[$type][$route] = $endpoint;
        krsort($this->routes[$type]);

        return $endpoint;
    }

    /**
     * Registers a get route
     *
     * @param $route string
     * @param $endpoint callable|Endpoint
     * @return Endpoint
     */
    public function get($route, $endpoint):Endpoint
    {
        return $this->registerRoute("get", $route, $endpoint);
    }

    /**
     * Registers a post route
     *
     * @param $route string
     * @param $endpoint callable|Endpoint
     * @return Endpoint
     */
    public function post($route, $endpoint):Endpoint
    {
        return $this->registerRoute("post", $route, $endpoint);
    }

    /**
     * Registers a put route
     *
     * @param $route string
     * @param $endpoint callable|Endpoint
     * @return Endpoint
     */
    public function put($route, $endpoint):Endpoint
    {
        return $this->registerRoute("put", $route, $endpoint);
    }

    /**
     * Registers a delete route
     *
     * @param $route string
     * @param $endpoint callable|Endpoint
     * @return Endpoint
     */
    public function delete($route, $endpoint):Endpoint
    {
        return $this->registerRoute("delete", $route, $endpoint);
    }

    /**
     * Return the response if appropriate or false if no response could be generated.
     *
     * @param mixed $request
     * @return bool|Response
     */
    protected function generateResponseForRequest($request = null)
    {
        /**
         * @var WebRequest $request;
         */

        $method = strtolower($request->server("REQUEST_METHOD"));

        if ($method == "options"){
            $response = new HtmlResponse();
            $response->setHeader("Access-Control-Allow-Origin", "*");
            $response->setHeader("Access-Control-Allow-Methods", "POST, PUT, GET, DELETE, OPTIONS");
            $response->setHeader("Access-Control-Allow-Headers", "Content-Type, Authorization");
            $response->setHeader("Access-Control-Max-Age", "86400");

            return $response;
        }

        $routes = $this->routes[$method];

        $remainingUrl = str_replace($this->matchingUrl, "", $request->urlPath);

        $response = null;

        foreach($routes as $route => $endpoint){
            /**
             * @var Endpoint $endpoint
             */
            $route = preg_replace("|/:([^/]+)|", "/(?<\\1>[^/]+)",$route);

            if (preg_match('|^'.$route.'|', $remainingUrl, $matches)){

                try {

                    $middlewareResponse = $this->processMiddlewares($this->middlewares, $matches, $request);

                    if ($middlewareResponse){
                        return $middlewareResponse;
                    }

                    $payload = $endpoint->processRequest($matches, $request);

                    if ($payload instanceof Response) {
                        $response = $payload;
                    } else {
                        $response = new JsonResponse();
                        $response->setContent($payload);
                    }
                } catch (ResourceNotFoundException $er){
                    $response = new NotFoundResponse();
                    $response->setContent("The resource could not be located.");
                } catch (\Throwable $er){
                    $response = new Response();
                    $response->setResponseCode(500);
                    $response->setResponseMessage("An internal error occurred.");
                }

                break;
            }
        }

        if ($response instanceof Response){
            $response->setHeader("Access-Control-Allow-Origin", "*");
            $response->setHeader("Access-Control-Allow-Methods", "POST, PUT, GET, DELETE, OPTIONS");
            $response->setHeader("Access-Control-Allow-Headers", "Content-Type, Authorization");
            $response->setHeader("Access-Control-Max-Age", "86400");

            return $response;
        }

        $response = new NotFoundResponse();
        $response->setContent("The resource could not be located.");

        return $response;
    }
}