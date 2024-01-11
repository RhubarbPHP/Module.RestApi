<?php

namespace Rhubarb\RestApi;

use Rhubarb\RestApi\ErrorHandlers\DefaultErrorHandler;
use Rhubarb\RestApi\ErrorHandlers\NotFoundHandler;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Psr7\Factory\ResponseFactory;

abstract class RhubarbRestAPIApplication
{
    /** @var App */
    protected $app;

    protected function registerErrorHandlers()
    {
        $container = $this->app->getContainer();
        $container['errorHandler'] = $container['phpErrorHandler'] = function () {
            return new DefaultErrorHandler();
        };
        $container['notFoundHandler'] = function () {
            return new NotFoundHandler();
        };
    }

    protected function registerMiddleware()
    {
        $this->app->add(function (Request $request, Response $response, callable $next) {
            $uri = $request->getUri();
            $path = $uri->getPath();
            // ensure all routes have a trailing slash for simplified router configuration
            if ($path !== '/' && substr($path, -1) !== '/') {
                $uri = $uri->withPath($path . '/');
                return $next($request->withUri($uri), $response);
            }

            return $next($request, $response);
        });
    }

    final protected function registerModule(RhubarbApiModule $module)
    {
        $module->registerErrorHandlers($this->app);
        $module->registerMiddleware($this->app);
    }

    /**
     * @return RhubarbApiModule[]
     */
    protected function registerModules()
    {
        return [];
    }

    abstract protected function registerRoutes();

    final public function initialise(): App
    {
        $responseFactory = new ResponseFactory();
        $this->app = new App($responseFactory);
        $this->registerErrorHandlers();
        $this->registerMiddleware();
        foreach ($this->registerModules() as $module) {
            $this->registerModule($module);
        }
        $this->registerRoutes();
        return $this->app;
    }
}
