<?php

namespace Rhubarb\RestApi;


use DI\Container;
use Psr\Http\Server\RequestHandlerInterface;
use Rhubarb\RestApi\ErrorHandlers\DefaultErrorHandler;
use Rhubarb\RestApi\ErrorHandlers\NotFoundHandler;
use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

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
        $this->app->addRoutingMiddleware();
        $this->app->add(function (Request $request, RequestHandlerInterface $requestHandlerInterface) {
            $uri = $request->getUri();
            $path = $uri->getPath();
            // ensure all routes have a trailing slash for simplified router configuration
            if ($path !== '/' && substr($path, -1) !== '/') {
                $uri = $uri->withPath($path . '/');
                return $requestHandlerInterface->handle($request->withUri($uri));
            }

            return $requestHandlerInterface->handle($request);
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
        $container = new Container();
        AppFactory::setContainer($container);   
        $this->app = AppFactory::create();
        $this->app->setBasePath('');
        $this->registerErrorHandlers();
        $this->registerMiddleware();
        foreach ($this->registerModules() as $module) {
            $this->registerModule($module);
        }
        $this->registerRoutes();
        return $this->app;
    }
}
