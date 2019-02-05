<?php

namespace Rhubarb\RestApi\Adapters;

use Slim\App;

class EntityAdapterRouterFactory
{
    public static function crud(App $app, string $entityAdapter): callable
    {
        return function () use ($entityAdapter, $app) {
            $app->get('/', self::entityAdapterList($entityAdapter));
            $app->get('/{id}', self::entityAdapterGet($entityAdapter));
            $app->post('/', self::entityAdapterPost($entityAdapter));
            $app->put('/{id}', self::entityAdapterPut($entityAdapter));
            $app->delete('/{id}', self::entityAdapterDelete($entityAdapter));
        };
    }

    public static function entityAdapterList(string $entityAdapter)
    {
        return function ($request, $response) use ($entityAdapter) {
            $method = 'list';
            return $entityAdapter::$method($request, $response);
        };
    }

    public static function entityAdapterGet(string $entityAdapter)
    {
        return function ($request, $response, $routeVariables) use ($entityAdapter) {
            $method = 'get';
            return $entityAdapter::$method($routeVariables['id'], $request, $response);
        };
    }

    public static function entityAdapterPost(string $entityAdapter)
    {
        return function ($request, $response) use ($entityAdapter) {
            $method = 'post';
            return $entityAdapter::$method($request, $response);
        };
    }

    public static function entityAdapterPut(string $entityAdapter)
    {
        return function ($request, $response, $routeVariables) use ($entityAdapter) {
            $method = 'put';
            return $entityAdapter::$method($routeVariables['id'], $request, $response);
        };
    }

    public static function entityAdapterDelete(string $entityAdapter)
    {
        return function ($request, $response, $routeVariables) use ($entityAdapter) {
            $method = 'delete';
            return $entityAdapter::$method($routeVariables['id'], $request, $response);
        };
    }
}

