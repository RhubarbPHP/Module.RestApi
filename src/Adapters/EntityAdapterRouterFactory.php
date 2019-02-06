<?php

namespace Rhubarb\RestApi\Adapters;

use Slim\App;

class EntityAdapterRouterFactory
{
    const LIST = 1;
    const ITEM_GET = 2;
    const ITEM_POST = 4;
    const ITEM_PUT = 8;
    const ITEM_DELETE = 16;
    const ALL = 31;

    /**
     * @param App $app
     * @param string $entityAdapter
     * @param int $allowed
     * @param callable|null $additional function(App $app, string $entityAdapter) If provided allows definition of additional routes for this base
     * @return callable
     */
    public static function crud(App $app, string $entityAdapter, $allowed = self::ALL, callable $additional = null): callable
    {
        return function () use ($entityAdapter, $app, $allowed, $additional) {
            $allowed & self::LIST && $app->get('/', self::entityAdapterList($entityAdapter));
            $allowed & self::ITEM_GET && $app->get('/{id}/', self::entityAdapterGet($entityAdapter));
            $allowed & self::ITEM_POST && $app->post('/', self::entityAdapterPost($entityAdapter));
            $allowed & self::ITEM_PUT && $app->put('/{id}/', self::entityAdapterPut($entityAdapter));
            $allowed & self::ITEM_DELETE && $app->delete('/{id}/', self::entityAdapterDelete($entityAdapter));
            if($additional !== null) {
                $additional($app, $entityAdapter);
            }
        };
    }

    public static function readOnly(App $app, string $entityAdapter): callable
    {
        return self::crud($app, $entityAdapter, self::LIST | self::ITEM_GET);
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

