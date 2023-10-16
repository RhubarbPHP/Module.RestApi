<?php

namespace Rhubarb\RestApi\Adapters;

use Rhubarb\RestApi\Entities\SearchResponseEntity;

use Slim\Http\Response;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class BaseEntityAdapter implements EntityAdapterInterface
{
    abstract protected function getEntityForId($id);

    abstract protected function deleteEntity($entity);

    abstract protected function getPayloadForEntity($entity, $resultList = false);

    abstract protected function getEntityForPayload($payload, $id = null);

    abstract protected function updateEntityWithPayload($entity, $payload);

    abstract protected function storeEntity($entity);

    abstract protected function getEntityList(
        Request $request,
        int $offset,
        int $pageSize,
        ?string $sort = null
    ): SearchResponseEntity;

    final public function list(Request $request, Response $response): Response
    {   
        $params = $request->getQueryParams();

        $offset = (int)$params['offset'] ?? ((int)$params['from'] ?? 1) - 1;
        $pageSize = (int)$params['pageSize'] ?? ($params['to'] ?? (10 - $offset));
        $sort = $params['sort'];

        $list = $this->getEntityList(
            $request,
            $offset,
            $pageSize,
            $sort
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

    final public function patch(Request $request, Response $response, $id): Response
    {
        $entity = $this->getEntityForId($id);
        $this->updateEntityWithPayload($entity, $request->getParsedBody());
        $this->storeEntity($entity);
        return $response->withStatus(204, 'No Content');
    }

    final public function put(Request $request, Response $response, $id): Response
    {
        $entity = $this->getEntityForPayload($request->getParsedBody(), $id);
        $this->storeEntity($entity);
        return $response->withJson($this->getPayloadForEntity($entity));
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
