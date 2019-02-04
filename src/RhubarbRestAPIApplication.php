<?php

namespace Rhubarb\RestApi;

use Rhubarb\RestApi\ErrorHandlers\DefaultErrorHandler;
use Slim\App;

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
    }

    protected function registerMiddleware()
    {

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
        $this->app = new App();
        $this->registerErrorHandlers();
        $this->registerMiddleware();
        foreach ($this->registerModules() as $module) {
            $this->registerModule($module);
        }
        $this->registerRoutes();
        return $this->app;
    }
}
