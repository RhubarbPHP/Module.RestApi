<?php

namespace Rhubarb\RestApi\Adapters;

use Rhubarb\RestApi\Entities\SearchResponseEntity;
use Slim\Http\Request;
use Slim\Http\Response;

abstract class BaseEntityAdapter implements EntityAdapterInterface
{
    abstract protected function getEntityForId($id);

    abstract protected function deleteEntity($entity);

    abstract protected function getPayloadForEntity($entity, $resultList = false);

    abstract protected function getEntityForPayload($payload, $id = null);

    abstract protected function storeEntity($entity);

    abstract protected function getEntityList(
        int $offset,
        int $pageSize,
        string $sort = null,
        Request $request
    ): SearchResponseEntity;

    final public function list(Request $request, Response $response): Response
    {
        $offset = (int)$request->getQueryParam('offset', $request->getQueryParam('from', 1) - 1);
        if ($offset < 0) {
            $offset = 0;
        }
        $pageSize = (int)$request->getQueryParam('pageSize', $request->getQueryParam('to', 10 - $offset) + 1);
        $sort = $request->getQueryParam('sort');

        $list = $this->getEntityList(
            $offset,
            $pageSize,
            $sort,
            $request
        );
        return $response
            ->withJson(array_map(
                function ($entity) {
                    return $this->getPayloadForEntity($entity, true);
                },
                $list->results
            ))
            ->withAddedHeader('X-Total', $list->total)
            ->withAddedHeader('X-Offset', $offset)
            ->withAddedHeader('X-PageSize', $pageSize)
            ->withAddedHeader('X-From', $offset + 1)
            ->withAddedHeader('X-To', $offset + $pageSize);
    }

    final public function get(Request $request, Response $response, $id): Response
    {
        return $response->withJson($this->getPayloadForEntity($this->getEntityForId($id)));
    }

    final public function put(Request $request, Response $response, $id): Response
    {
        $entity = $this->getEntityForPayload($request->getParsedBody(), $id);
        $this->storeEntity($entity);
        return $response->withJson($this->getPayloadForEntity(
            $this->getPayloadForEntity($entity)
        ));
    }

    final public function post(Request $request, Response $response): Response
    {
        return $this->put($request, $response, null);
    }

    final public function delete(Request $request, Response $response, $id): Response
    {
        $this->deleteEntity($this->getEntityForId($id));
        return $response;
    }
}
