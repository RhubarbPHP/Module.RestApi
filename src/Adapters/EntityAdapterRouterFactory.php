<?php

namespace Rhubarb\RestApi\Adapters;

use Slim\App;

class EntityAdapterRouterFactory
{
    const LIST = 1;
    const ITEM_GET = 2;
    const ITEM_POST = 4;
    const ITEM_PUT = 8;
    const ITEM_PATCH = 16;
    const ITEM_DELETE = 32;
    const ALL = 63;

    /**
     * @param App $app
     * @param string $entityAdapter
     * @param int $allowed
     * @param callable|null $additional function(App $app, EntityAdapterInterface $entityAdapter) If provided allows definition of additional routes for this base
     * @return callable
     */
    public static function crud(
        App $app,
        string $entityAdapter,
        $allowed = self::ALL,
        callable $additional = null
    ): callable {
        return function () use ($entityAdapter, $app, $allowed, $additional) {
            $entityAdapter = new $entityAdapter();
            $allowed & self::LIST && $app->get(
                '/',
                self::entityAdapterHandler($entityAdapter, 'list')
            );
            $allowed & self::ITEM_POST && $app->post(
                '/',
                self::entityAdapterHandler($entityAdapter, 'post')
            );
            $allowed & self::ITEM_GET && $app->get(
                '/{id}/',
                self::entityAdapterWithRouteIDHandler($entityAdapter, 'get')
            );
            $allowed & self::ITEM_PUT && $app->put(
                '/{id}/',
                self::entityAdapterWithRouteIDHandler($entityAdapter, 'put')
            );
            $allowed & self::ITEM_PATCH && $app->patch(
                '/{id}/',
                self::entityAdapterWithRouteIDHandler($entityAdapter, 'patch')
            );
            $allowed & self::ITEM_DELETE && $app->delete(
                '/{id}/',
                self::entityAdapterWithRouteIDHandler($entityAdapter, 'delete')
            );
            if ($additional !== null) {
                $additional($app, $entityAdapter);
            }
        };
    }

    /**
     * @param App $app
     * @param string $entityAdapter
     * @param callable|null $additional function(App $app, string $entityAdapter) If provided allows definition of additional routes for this base
     * @return callable
     */
    public static function readOnly(App $app, string $entityAdapter, callable $additional = null): callable
    {
        return self::crud($app, $entityAdapter, self::LIST | self::ITEM_GET, $additional);
    }

    private static function entityAdapterWithRouteIDHandler(EntityAdapterInterface $entityAdapter, $adapterMethod)
    {
        return function ($request, $response, $routeVariables) use ($entityAdapter, $adapterMethod) {
            return $entityAdapter->$adapterMethod($request, $response, $routeVariables['id']);
        };
    }

    public static function entityAdapterHandler(EntityAdapterInterface $entityAdapter, $adapterMethod)
    {
        return function (...$params) use ($entityAdapter, $adapterMethod) {
            return $entityAdapter->$adapterMethod(...$params);
        };
    }
}

