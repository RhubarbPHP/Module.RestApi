<?php

namespace Rhubarb\RestApi\Adapters;

use Rhubarb\RestApi\Entities\SearchCriteriaEntity;
use Rhubarb\RestApi\Entities\SearchResponseEntity;
use Slim\Http\Request;

abstract class EntityAdapter extends BaseEntityAdapter
{
    abstract protected function performSearch(SearchResponseEntity $response);

    protected function getSearchResponseEntity(SearchCriteriaEntity $criteria): SearchResponseEntity
    {
        return new SearchResponseEntity($criteria);
    }

    protected function getSearchCriteriaEntity(
        int $offset,
        int $pageSize,
        string $sort = null,
        Request $request
    ): SearchCriteriaEntity {
        return new SearchCriteriaEntity($offset, $pageSize, $sort);
    }

    final protected function getEntityList(
        int $offset,
        int $pageSize,
        string $sort = null,
        Request $request
    ): SearchResponseEntity {
        $response = new SearchResponseEntity($this->getSearchCriteriaEntity($offset, $pageSize, $sort, $request));
        $this->performSearch($response);
        return $response;
    }
}
