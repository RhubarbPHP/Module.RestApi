<?php

namespace Rhubarb\RestApi\Adapters;

use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\RestApi\Exceptions\ResourceNotFoundException;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Models\Model;

class ModelResourceAdapter extends ResourceAdapter
{
    private $modelClassName;
    private $resourceClass;

    public function __construct($resourceClass, $modelClassName)
    {
        $this->modelClassName = $modelClassName;
        $this->resourceClass = $resourceClass;
    }

    /**
     * @param $id
     * @return object
     * @throws ResourceNotFoundException
     */
    public function makeResourceByIdentifier($id)
    {
        try {
            $model = new $this->modelClassName($id);
        } catch(RecordNotFoundException $er){
            throw new ResourceNotFoundException();
        }

        return $this->makeResourceFromData($model);
    }

    public function makeModelFromResource($resource)
    {
        $modelClass = $this->modelClassName;
        /**
         * @var $model Model
         */
        $model = new $modelClass();

        $this->applyResourceToModel($resource, $model);

        return $model;
    }

    private function getResourcePropertyMap()
    {
        $reflection = new \ReflectionClass($this->resourceClass);
        $props = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $lcaseProps = [];

        foreach($props as $prop) {
            $lcaseProps[strtolower($prop->name)] = $prop->name;
        }

        return $lcaseProps;
    }

    public function makeResourceFromData($data)
    {
        $lcaseProps = $this->getResourcePropertyMap();

        $reflection = new \ReflectionClass($this->resourceClass);
        $resource = $reflection->newInstance();
        $resource->id = $data->getUniqueIdentifier();

        foreach($data->exportData() as $prop => $value) {
            if (isset($lcaseProps[strtolower($prop)])){
                $propName = $lcaseProps[strtolower($prop)];
                $resource->$propName = $value;
            }
        }

        return $resource;
    }

    private function applyResourceToModel($resource, Model $model)
    {
        $lcaseProps = [];

        foreach($resource as $key => $value){
            $lcaseProps[strtolower($key)] = $key;
        }

        $columns = $model->getSchema()->getColumns();

        foreach($columns as $column){
            $lowerCaseColumnName = strtolower($column->columnName);

            if (isset($lcaseProps[$lowerCaseColumnName])){
                $model[$column->columnName] = $resource[$lcaseProps[$lowerCaseColumnName]];
            }
        }

        return $model;
    }


    public function putResource($resource)
    {
        $modelClass = $this->modelClassName;
        /**
         * @var $model Model
         */
        $model = new $modelClass($resource["id"]);

        $this->applyResourceToModel($resource, $model);

        $model->save();

        return $model;
    }

    protected function filterCollection(Collection $collection, $params, ?WebRequest $request = null)
    {

    }

    private function getCollection($rangeStart, $rangeEnd, $params, ?WebRequest $request = null)
    {
        $modelClassName = $this->modelClassName;

        $list = $modelClassName::all();
        $list->setRange($rangeStart, $rangeEnd);

        $this->filterCollection($list, $params, $request);

        return $list;
    }

    protected function getItems($rangeStart, $rangeEnd, $params, ?WebRequest $request = null)
    {
        $collection = $this->getCollection($rangeStart, $rangeEnd, $params, $request);

        $resources = [];

        foreach($collection as $item){
            $resources[] = $this->makeResourceFromData($item);
        }

        return $resources;
    }

    protected function countItems($rangeStart, $rangeEnd, $params, ?WebRequest $request = null)
    {
        return count($this->getCollection($rangeStart, $rangeEnd, $params, $request));
    }

    public function post($payload, $params, WebRequest $request)
    {
        $model = $this->makeModelFromResource($payload);
        $model->save();

        return $this->makeResourceFromData($model);
    }
}