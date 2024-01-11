<?php

namespace Rhubarb\RestApi\Adapters;

use Slim\App;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Routing\RouteCollectorProxy;

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
     * @param App $entityAdapter
     * @param int $allowed
     * @param callable|null $additional function(RouteCollectorProxy $group, EntityAdapterInterface $entityAdapter) If provided allows definition of additional routes for this base
     * @return callable
     */
    public static function crud(
        App $entityAdapter,
        $allowed = self::ALL,
        callable $additional = null
    ): callable {
        return function (RouteCollectorProxy $group) use ($entityAdapter, $allowed, $additional) {
            $responseFactory = new ResponseFactory();
            $entityAdapter = new $entityAdapter($responseFactory);
            $allowed & self::LIST && $group->get(
                '/',
                self::entityAdapterHandler($entityAdapter, 'list')
            );
            $allowed & self::ITEM_POST && $group->post(
                '/',
                self::entityAdapterHandler($entityAdapter, 'post')
            );
            $allowed & self::ITEM_GET && $group->get(
                '/{id}/',
                self::entityAdapterWithRouteIDHandler($entityAdapter, 'get')
            );
            $allowed & self::ITEM_PUT && $group->put(
                '/{id}/',
                self::entityAdapterWithRouteIDHandler($entityAdapter, 'put')
            );
            $allowed & self::ITEM_PATCH && $group->patch(
                '/{id}/',
                self::entityAdapterWithRouteIDHandler($entityAdapter, 'patch')
            );
            $allowed & self::ITEM_DELETE && $group->delete(
                '/{id}/',
                self::entityAdapterWithRouteIDHandler($entityAdapter, 'delete')
            );
            if ($additional !== null) {
                $additional($group, $entityAdapter);
            }
        };
    }

    /**
     * @param App $entityAdapter
     * @param callable|null $additional function(App $app, string $entityAdapter) If provided allows definition of additional routes for this base
     * @return callable
     */
    public static function readOnly( App $entityAdapter, callable $additional = null): callable
    {
        return self::crud( $entityAdapter, self::LIST | self::ITEM_GET, $additional);
    }

    private static function entityAdapterWithRouteIDHandler(App $entityAdapter, $adapterMethod)
    {
        return function ($request, $response, $routeVariables) use ($entityAdapter, $adapterMethod) {
            return $entityAdapter->$adapterMethod($request, $response, $routeVariables['id']);
        };
    }

    public static function entityAdapterHandler(App $entityAdapter, $adapterMethod)
    {
        return function (...$params) use ($entityAdapter, $adapterMethod) {
            return $entityAdapter->$adapterMethod(...$params);
        };
    }
}

