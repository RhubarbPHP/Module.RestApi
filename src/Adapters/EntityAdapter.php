<?php

namespace Rhubarb\RestApi\Adapters;

use Rhubarb\RestApi\Entities\SearchCriteriaEntity;
use Rhubarb\RestApi\Entities\SearchResponseEntity;
use Slim\Http\Request;

abstract class EntityAdapter extends BaseEntityAdapter
{
    abstract protected static function performSearch(SearchResponseEntity $response);

    protected static function getSearchResponseEntity(SearchCriteriaEntity $criteria): SearchResponseEntity
    {
        return new SearchResponseEntity($criteria);
    }

    protected static function getSearchCriteriaEntity(
        int $offset,
        int $pageSize,
        string $sort = null,
        Request $request
    ): SearchCriteriaEntity {
        return new SearchCriteriaEntity($offset, $pageSize, $sort);
    }

    final protected static function getEntityList(
        int $offset,
        int $pageSize,
        string $sort = null,
        Request $request
    ): SearchResponseEntity {
        $response = new SearchResponseEntity(static::getSearchCriteriaEntity($offset, $pageSize, $sort, $request));
        static::performSearch($response);
        return $response;
    }
}
