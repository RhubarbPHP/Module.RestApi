<?php

namespace Rhubarb\RestApi\Adapters\Stem;

use Rhubarb\RestApi\Adapters\BaseEntityAdapter;
use Rhubarb\RestApi\Entities\SearchCriteriaEntity;
use Rhubarb\RestApi\Entities\SearchResponseEntity;
use Rhubarb\RestApi\Exceptions\ResourceNotFoundException;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Models\Model;
use Slim\Http\Request;

/**
 * Class LegacyStemEntityAdapter
 * @package Rhubarb\RestApi\Adapters\Stem
 * @deprecated Use \Rhubarb\RestApi\Adapters\EntityAdapter with use cases and proper entities
 */
abstract class LegacyStemEntityAdapter extends BaseEntityAdapter
{
    abstract protected function getModelClass(): string;

    /**
     * @param $id
     * @return Model
     * @throws ResourceNotFoundException
     */
    protected function getEntityForId($id)
    {
        try {
            /** @var Model $modelClass */
            $modelClass = $this->getModelClass();
            return new $modelClass($id);
        } catch (RecordNotFoundException $exception) {
            throw new ResourceNotFoundException($exception->getMessage(), $exception);
        }
    }

    /**
     * @param Model $entity
     * @param bool $resultList
     * @return array
     */
    protected function getPayloadForEntity($entity, $resultList = false)
    {
        return $entity->exportPublicData();
    }

    protected function getEntityForPayload($payload, $id = null)
    {
        $modelClass = $this->getModelClass();
        /** @var Model $model */
        $model = new $modelClass($id);
        $model->importData($payload);
        return $model;
    }

    /**
     * @param Model $entity
     * @throws \Rhubarb\Stem\Exceptions\DeleteModelException
     */
    final protected function deleteEntity($entity)
    {
        $entity->delete();
    }

    /**
     * @param Request $request
     * @return Filter[]
     */
    protected function getListFilterForRequest(Request $request): array
    {
        return [];
    }

    /**
     * @param int $offset
     * @param int $pageSize
     * @param Request $request
     * @return Collection
     */
    final protected function getEntityList(
        int $offset,
        int $pageSize,
        string $sort = null,
        Request $request
    ): SearchResponseEntity {
        $criteria = new SearchCriteriaEntity($offset, $pageSize, $sort);
        $response = new SearchResponseEntity($criteria);
        /** @var Model $modelClass */
        $modelClass = $this->getModelClass();
        $collection = $modelClass::find(...$this->getListFilterForRequest($request))->setRange($offset, $pageSize);
        $response->total = $collection->count();
        foreach ($collection as $model) {
            $response->results[] = $model;
        }
        return $response;
    }

    /**
     * @param Model $entity
     * @throws \Rhubarb\Stem\Exceptions\ModelConsistencyValidationException
     * @throws \Rhubarb\Stem\Exceptions\ModelException
     */
    protected function storeEntity($entity)
    {
        $entity->save();
    }
}