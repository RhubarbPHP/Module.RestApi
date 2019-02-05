<?php

namespace Rhubarb\RestApi\Adapters;

use Rhubarb\Crown\DependencyInjection\Container;
use Slim\App;

class EntityAdapterRouterFactory
{
    public static function crud(App $app, string $entityAdapter, bool $di = false): callable
    {
        return function () use ($entityAdapter, $app, $di) {
            $app->get('/', self::entityAdapterList($entityAdapter, $di));
            $app->get('/{id}', self::entityAdapterGet($entityAdapter, $di));
            $app->post('/', self::entityAdapterPost($entityAdapter, $di));
            $app->put('/{id}', self::entityAdapterPut($entityAdapter, $di));
            $app->delete('/{id}', self::entityAdapterDelete($entityAdapter, $di));
        };
    }

    public static function entityAdapterList(string $entityAdapter, bool $di = false)
    {
        return function ($request, $response) use ($entityAdapter, $di) {
            if ($di) {
                $entityAdapter = get_class(Container::current()->getInstance($entityAdapter));
            }
            return $entityAdapter::list($request, $response);
        };
    }

    public static function entityAdapterGet(string $entityAdapter, bool $di = false)
    {
        return function ($request, $response, $routeVariables) use ($entityAdapter, $di) {
            if ($di) {
                $entityAdapter = get_class(Container::current()->getInstance($entityAdapter));
            }
            return $entityAdapter::get($routeVariables['id'], $request, $response);
        };
    }

    public static function entityAdapterPost(string $entityAdapter, bool $di = false)
    {
        return function ($request, $response) use ($entityAdapter, $di) {
            if ($di) {
                $entityAdapter = get_class(Container::current()->getInstance($entityAdapter));
            }
            return $entityAdapter::post($request, $response);
        };
    }

    public static function entityAdapterPut(string $entityAdapter, bool $di = false)
    {
        return function ($request, $response, $routeVariables) use ($entityAdapter, $di) {
            if ($di) {
                $entityAdapter = get_class(Container::current()->getInstance($entityAdapter));
            }
            return $entityAdapter::put($routeVariables['id'], $request, $response);
        };
    }

    public static function entityAdapterDelete(string $entityAdapter, bool $di = false)
    {
        return function ($request, $response, $routeVariables) use ($entityAdapter, $di) {
            if ($di) {
                $entityAdapter = get_class(Container::current()->getInstance($entityAdapter));
            }
            return $entityAdapter::delete($routeVariables['id'], $request, $response);
        };
    }
}

