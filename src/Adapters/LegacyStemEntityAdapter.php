<?php

namespace Rhubarb\RestApi\Adapters;

use Rhubarb\RestApi\Exceptions\ResourceNotFoundException;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Models\Model;

class LegacyStemEntityAdapter extends StemEntityAdapter
{
    abstract protected static function getModelClass(): string;

    protected static function getEntityForId($id): Model
    {
        try {
            /** @var Model $modelClass */
            $modelClass = self::getModelClass();
            return new $modelClass($id);
        } catch (RecordNotFoundException $exception) {
            throw new ResourceNotFoundException($exception->getMessage(), $exception);
        }
    }

    protected static function getPayloadForEntity(Model $model): array
    {
        return $model->exportPublicData();
    }

    protected static function createEntity($payload, $id = null): Model
    {
        $modelClass = self::getModelClass();
        /** @var Model $model */
        $model = new $modelClass($id);
        $model->importData($payload);
        $model->save();
        return $model;
    }

    protected static function deleteEntity(Model $entity)
    {
        $entity->delete();
    }
}
