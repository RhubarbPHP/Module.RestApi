<?php

namespace Rhubarb\RestApi\Adapters;

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Models\Model;
use Slim\Http\Request;
use Slim\Http\Response;

abstract class StemEntityAdapter implements EntityAdapterInterface
{
    abstract protected static function getEntityForId($id): Model;

    abstract protected static function deleteEntity(Model $entity);

    abstract protected static function getPayloadForEntity(Model $model): array;

    abstract protected static function createEntity($payload, $id = null): Model;

    protected static function getEntityList(int $offset, int $pageSize, Request $request): Collection
    {
        /** @var Model $modelClass */
        $modelClass = self::getModelClass();
        return $modelClass::all()->setRange($offset, $pageSize);
    }

    protected static function getPayloadForEntityList(Collection $collection, $request): array
    {
        $payloads = [];
        foreach ($collection as $entity) {
            $payloads[] = self::getPayloadForEntity($entity);
        }
        return $payloads;
    }

    final public static function list(Request $request, Response $response): Response
    {
        $offset = (int)$request->getQueryParam('offset', $request->getQueryParam('from', 1) - 1);
        $pageSize = (int)$request->getQueryParam('pageSize', $request->getQueryParam('to', 10 - $offset));

        $list = self::getEntityList(
            $offset,
            $pageSize,
            $request
        );
        $total = $list->count();
        return $response
            ->withJson(self::getPayloadForEntityList($list, $request))
            ->withAddedHeader('X-Total', $total)
            ->withAddedHeader('X-Offset', $offset)
            ->withAddedHeader('X-PageSize', $pageSize)
            ->withAddedHeader('X-From', $offset + 1)
            ->withAddedHeader('X-To', $offset + $pageSize);
    }

    final public static function get($id, Request $request, Response $response): Response
    {
        return $response->withJson(self::getPayloadForEntity(self::getEntityForId($id)));
    }

    final public static function post(Request $request, Response $response): Response
    {
        return $response->withJson(self::getPayloadForEntity(
            self::createEntity($request->getParsedBody())
        ));
    }

    final public static function put($id, Request $request, Response $response): Response
    {
        return $response->withJson(self::getPayloadForEntity(
            self::createEntity($request->getParsedBody(), $id)
        ));
    }

    final public static function delete($id, Request $request, Response $response): Response
    {
        self::deleteEntity(self::getEntityForId($id));

        return $response;
    }
}
