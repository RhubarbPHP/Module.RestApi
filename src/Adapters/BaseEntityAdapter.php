<?php

namespace Rhubarb\RestApi\Adapters;

use Rhubarb\RestApi\Entities\SearchResponseEntity;
use Slim\Http\Request;
use Slim\Http\Response;

abstract class BaseEntityAdapter implements EntityAdapterInterface
{
    abstract protected static function getEntityForId($id);

    abstract protected static function deleteEntity($entity);

    abstract protected static function getPayloadForEntity($entity, $resultList = false): array;

    abstract protected static function getEntityForPayload($payload, $id = null);

    abstract protected static function storeEntity($entity);

    abstract protected static function getEntityList(
        int $offset,
        int $pageSize,
        string $sort = null,
        Request $request
    ): SearchResponseEntity;

    final public static function list(Request $request, Response $response): Response
    {
        $offset = (int)$request->getQueryParam('offset', $request->getQueryParam('from', 1) - 1);
        $pageSize = (int)$request->getQueryParam('pageSize', $request->getQueryParam('to', 10 - $offset));
        $sort = $request->getQueryParam('sort');

        $list = static::getEntityList(
            $offset,
            $pageSize,
            $sort,
            $request
        );
        return $response
            ->withJson(array_map(
                function ($entity) {
                    return static::getPayloadForEntity($entity, true);
                },
                $list->results
            ))
            ->withAddedHeader('X-Total', $list->total)
            ->withAddedHeader('X-Offset', $offset)
            ->withAddedHeader('X-PageSize', $pageSize)
            ->withAddedHeader('X-From', $offset + 1)
            ->withAddedHeader('X-To', $offset + $pageSize);
    }

    final public static function get($id, Request $request, Response $response): Response
    {
        return $response->withJson(static::getPayloadForEntity(static::getEntityForId($id)));
    }

    final public static function put($id, Request $request, Response $response): Response
    {
        $entity = static::getEntityForPayload($request->getParsedBody(), $id);
        static::storeEntity($entity);
        return $response->withJson(static::getPayloadForEntity(
            static::getPayloadForEntity($entity)
        ));
    }

    final public static function post(Request $request, Response $response): Response
    {
        return self::put(null, $request, $response);
    }

    final public static function delete($id, Request $request, Response $response): Response
    {
        static::deleteEntity(static::getEntityForId($id));
        return $response;
    }
}
