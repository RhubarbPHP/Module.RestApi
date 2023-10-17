<?php

namespace Rhubarb\RestApi;


use Rhubarb\Crown\DependencyInjection\Container;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;
use Throwable;

abstract class RhubarbRestAPIApplication
{
    /** @var App */
    protected $app;

    protected function registerErrorHandlers()
    {
        $app = $this->app;
        $errorMiddleware = $this->app->addErrorMiddleware(true, true, true);

        $errorMiddleware->setDefaultErrorHandler(function (Request $request, Throwable $exception = null, bool $displayErrorDetails) use ($app)
        {
            $code = $exception->getCode();
    
            if (!($code > 199 && $code < 600) || !$code) {
                error_log($exception->getMessage() . ' ' . $exception->getFile() . ':' . $exception->getLine());
                $error = 'Something went wrong';
                $code = 500;
            } else {
                $error = $exception->getMessage();
            }
            $response  = $app->getResponseFactory()->createResponse();
            return $response->withStatus($code, $error);
            
        });
        $errorMiddleware->setErrorHandler(HttpNotFoundException::class, function (Request $request, Throwable $exception = null, bool $displayErrorDetails) use ($app)
        {
            $response  = $app->getResponseFactory()->createResponse();
            return $response->withStatus(404);
            
        });  
        return $errorMiddleware;
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
