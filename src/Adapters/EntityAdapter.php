<?php

namespace Rhubarb\RestApi\Adapters;

use Rhubarb\RestApi\Entities\SearchCriteriaEntity;
use Rhubarb\RestApi\Entities\SearchResponseEntity;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class EntityAdapter extends BaseEntityAdapter
{
    abstract protected function performSearch(SearchResponseEntity $response);

    protected function getSearchResponseEntity(SearchCriteriaEntity $criteria): SearchResponseEntity
    {
        return new SearchResponseEntity($criteria);
    }

    protected function getSearchCriteriaEntity(
        Request $request,
        int $offset,
        int $pageSize,
        string $sort = null
    ): SearchCriteriaEntity {
        return new SearchCriteriaEntity($offset, $pageSize, $sort);
    }

    final protected function getEntityList(
        Request $request,
        int $offset,
        int $pageSize,
        ?string $sort = null
    ): SearchResponseEntity {
        $response = new SearchResponseEntity($this->getSearchCriteriaEntity($request, $offset, $pageSize, $sort));
        $this->performSearch($response);
        return $response;
    }
}
