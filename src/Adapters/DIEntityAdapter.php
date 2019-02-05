<?php

namespace Rhubarb\RestApi\Adapters;

use Rhubarb\Crown\DependencyInjection\Container;
use Slim\Http\Request;
use Slim\Http\Response;

abstract class DIEntityAdapter implements EntityAdapterInterface
{
    private static function getEntityAdapter(): string
    {
        $getInstance = function (): EntityAdapterInterface {
            return Container::instance(static::class);
        };

        return get_class($getInstance());
    }

    final public static function list(Request $request, Response $response): Response
    {
        $entityAdapter = self::getEntityAdapter();
        $method = __METHOD__;
        return $entityAdapter::$method(...func_get_args());
    }

    final public static function get($id, Request $request, Response $response): Response
    {
        $entityAdapter = self::getEntityAdapter();
        $method = __METHOD__;
        return $entityAdapter::$method(...func_get_args());
    }

    final public static function post(Request $request, Response $response): Response
    {
        $entityAdapter = self::getEntityAdapter();
        $method = __METHOD__;
        return $entityAdapter::$method(...func_get_args());
    }

    final public static function put($id, Request $request, Response $response): Response
    {
        $entityAdapter = self::getEntityAdapter();
        $method = __METHOD__;
        return $entityAdapter::$method(...func_get_args());
    }

    final public static function delete($id, Request $request, Response $response): Response
    {
        $entityAdapter = self::getEntityAdapter();
        $method = __METHOD__;
        return $entityAdapter::$method(...func_get_args());
    }
}
