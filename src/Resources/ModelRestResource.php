<?php

/*
 *	Copyright 2015 RhubarbPHP
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

namespace Rhubarb\RestApi\Resources;

require_once __DIR__ . '/ModelRestResource.php';

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\RestApi\Exceptions\RestImplementationException;
use Rhubarb\RestApi\Exceptions\UpdateException;
use Rhubarb\RestApi\UrlHandlers\RestHandler;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Relationships\ManyToMany;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\SolutionSchema;

/**
 * An ApiResource that wraps a business model and provides some of the heavy lifting.
 */
abstract class ModelRestResource extends RestResource
{
    private static $modelToResourceMapping = [];

    public function __construct($resourceIdentifier = null, $parentResource = null)
    {
        parent::__construct($resourceIdentifier, $parentResource);
    }

    public static function registerModelToResourceMapping($modelName, $resourceClassName)
    {
        self::$modelToResourceMapping[$modelName] = $resourceClassName;
    }

    public static function getRestResourceForModel(Model $model)
    {
        $modelName = $model->getModelName();

        if (!isset(self::$modelToResourceMapping[$modelName])) {
            throw new RestImplementationException("The model $modelName does not have an associated rest resource.");
        }

        $class = self::$modelToResourceMapping[$modelName];

        $resource = new $class();
        $resource->setModel($model);

        return $resource;
    }

    public static function getRestResourceForModelName($modelName)
    {
        if (!isset(self::$modelToResourceMapping[$modelName])) {
            return false;
        }

        $class = self::$modelToResourceMapping[$modelName];

        $resource = new $class();

        return $resource;
    }

    public static function clearRestResources()
    {
        self::$modelToResourceMapping = [];
    }

    protected function createModelCollection()
    {
        // If we have a parent resource we will look to see if we can exploit a relationship
        // to use as our starting collection. This will ensure we only serve the correct
        // resources
        if ($this->parentResource instanceof ModelRestResource) {
            // See there is a relationship between these two models that can be exploited
            $parentModelName = $this->parentResource->getModelName();
            $relationships = SolutionSchema::getAllRelationshipsForModel($parentModelName);

            // Our model name
            $modelName = $this->getModelName();

            foreach ($relationships as $relationship) {
                if ($relationship instanceof ManyToMany) {
                    if ($relationship->getRightModelName() == $modelName) {
                        return $relationship->fetchFor($this->parentResource->getModel());
                    }
                }

                if ($relationship instanceof OneToMany) {
                    if ($relationship->getTargetModelName() == $modelName) {
                        return $relationship->fetchFor($this->parentResource->getModel());
                    }
                }
            }
        }

        return new Collection($this->getModelName());
    }

    protected function getModelCollection()
    {
        $collection = $this->createModelCollection();

        $this->filterModelCollectionForSecurity($collection);

        return $collection;
    }

    public function getCollection()
    {
        return new ModelRestCollection($this, $this->parentResource, $this->getModelCollection());
    }

    protected function getModelAsResource($columns)
    {
        $model = $this->getModel();

        $extract = [];

        $relationships = null;

        foreach ($columns as $label => $column) {
            $apiLabel = (is_numeric($label)) ? $column : $label;

            $value = $model->$column;

            // We can't pass objects back through the API! Let's get a JSON friendly structure instead.
            if ( is_object( $value ) ){
                // This seems strange however if we just used json_encode we'd be passing the encoded version
                // back as a string. We decode to get the original structure back again.
                $value = json_decode( json_encode($value) );
            }

            if ( $value !== null ) {
                $extract[$apiLabel] = $value;
            } else {
                if ($relationships == null) {
                    $relationships = SolutionSchema::getAllRelationshipsForModel($model->getModelName());
                }

                // Look for resource modifiers after the column name
                $modifier = "";
                $urlSuffix = false;
                $relatedField = false;

                if (stripos($column, ":") !== false) {
                    $parts = explode(":", $column);
                    $column = $parts[0];

                    if (is_numeric($label)) {
                        $apiLabel = $column;
                    }

                    $modifier = strtolower($parts[1]);

                    if (sizeof($parts) > 2) {
                        $urlSuffix = $parts[2];
                    }
                } else {
                    if (stripos($column, ".") !== false) {
                        $parts = explode(".", $column, 2);
                        $column = $parts[0];
                        $relatedField = $parts[1];

                        if (is_numeric($label)) {
                            $apiLabel = $parts[1];
                        }
                    }
                }

                if (isset($relationships[$column])) {
                    $navigationValue = $model[$column];
                    $navigationResource = false;

                    if ($navigationValue instanceof Model) {
                        if ($relatedField) {
                            eval('$extract[ $apiLabel ] = $navigationValue->' . str_replace(".", "->",
                                    $relatedField) . ';');
                            continue;
                        }

                        $navigationResource = self::getRestResourceForModel($navigationValue);

                        if ($navigationResource === false) {
                            throw new RestImplementationException(print_r($navigationValue, true));
                            continue;
                        }
                    }

                    if ($navigationValue instanceof Collection) {
                        $navigationResource = self::getRestResourceForModelName(SolutionSchema::getModelNameFromClass($navigationValue->getModelClassName()));

                        if ($navigationResource === false) {
                            continue;
                        }

                        $navigationResource = $navigationResource->getCollection();
                        $navigationResource->setModelCollection($navigationValue);
                    }

                    if ($navigationResource) {
                        switch ($modifier) {
                            case "summary":
                                $extract[$apiLabel] = $navigationResource->summary();
                                break;
                            case "link":
                                $link = $navigationResource->link();

                                if ($urlSuffix != "") {
                                    $ourHref = $this->getHref($_SERVER["SCRIPT_NAME"]);

                                    // Override the href with this appendage instead.
                                    $link->href = $ourHref . $urlSuffix;
                                }

                                $extract[$apiLabel] = $link;

                                break;
                            default:
                                $extract[$apiLabel] = $navigationResource->get();
                                break;
                        }
                    }
                }
            }
        }

        return $extract;
    }

    public function summary(RestHandler $handler = null)
    {
        $resource = parent::get($handler);

        $data = $this->getModelAsResource($this->getSummaryColumns());

        foreach ($data as $key => $value) {
            $resource->$key = $value;
        }

        return $resource;
    }

    public function get(RestHandler $handler = null)
    {
        $resource = parent::get($handler);

        $data = $this->getModelAsResource($this->getColumns());

        foreach ($data as $key => $value) {
            $resource->$key = $value;
        }

        return $resource;
    }

    public function head(RestHandler $handler = null)
    {
        $resource = parent::get($handler);

        if (!isset($resource->resource)) {
            $resource->resource = new \stdClass();
        }

        $data = $this->getModelAsResource($this->getHeadColumns());

        foreach ($data as $key => $value) {
            $resource->resource->$key = $value;
        }

        return $resource;
    }

    /**
     * Override to control the columns returned in HEAD requests
     *
     * @return string[]
     */
    protected function getSummaryColumns()
    {
        $model = $this->getModel();
        return [$model->getLabelColumnName()];
    }

    /**
     * Override to control the columns returned in GET requests
     *
     * @return string[]
     */
    protected function getColumns()
    {
        $model = $this->getModel();

        return $model->PublicPropertyList;
    }

    private $model = false;

    /**
     * get's the Model object used to populate the REST resource
     *
     * This is public as it is sometimes required by child handlers to check security etc.
     *
     * @throws \Rhubarb\RestApi\Exceptions\RestImplementationException
     * @return Collection|null
     */
    public function getModel()
    {
        if ($this->model === false) {
            $this->model = SolutionSchema::getModel($this->getModelName(), $this->id);
        }

        if (!$this->model) {
            throw new RestImplementationException("There is no matching resource for this url");
        }

        return $this->model;
    }

    /**
     * Returns the name of the model to use for this resource.
     *
     * @return string
     */
    public abstract function getModelName();

    /**
     * Sets the model that should be used for the operations of this resource.
     *
     * This is normally only used by collections for efficiency (to avoid constructing additional objects)
     *
     * @param Model $model
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        $this->setID($model->UniqueIdentifier);
    }

    /**
     * Override to respond to the event of a new model being created through a POST
     *
     * @param $model
     * @param $restResource
     */
    public function afterModelCreated($model, $restResource)
    {

    }

    /**
     * Override to response to the event of a model being updated through a PUT
     *
     * @param $model
     * @param $restResource
     */
    protected function beforeModelUpdated($model, $restResource)
    {

    }

    public function put($restResource, RestHandler $handler = null)
    {
        try {
            $model = $this->getModel();
            $model->importData($restResource);

            $this->beforeModelUpdated($model, $restResource);

            $model->save();

            return true;
        } catch (RecordNotFoundException $er) {
            throw new UpdateException("That record could not be found.");
        } catch (\Exception $er) {
            throw new UpdateException($er->getMessage());
        }
    }

    public function delete(RestHandler $handler = null)
    {
        try {
            $model = $this->getModel();
            $model->delete();

            return true;
        } catch (\Exception $er) {
            return false;
        }
    }

    /**
     * Override to filter a model collection to apply any necessary filters only when this is the specific resource being fetched
     *
     * The default handling applies the same filters as filterModelCollectionContainer, so don't call the parent implementation unless you want that.
     *
     * @param Collection $collection
     */
    public function filterModelResourceCollection(Collection $collection)
    {
        $this->filterModelCollectionContainer($collection);
    }

    /**
     * Override to filter a model collection to apply any necessary filters only when this is a REST parent of the specific resource being fetched
     *
     * @param Collection $collection
     */
    public function filterModelCollectionContainer(Collection $collection)
    {
    }

    public function filterModelCollectionForModifiedSince(Collection $collection, RhubarbDateTime $since)
    {
        throw new RestImplementationException("A collection filtered by modified date was requested however this resource does not support it.");
    }

    /**
     * Override to filter a model collection generated by a ModelRestCollection
     *
     * Normally used by root collections to filter based on authentication permissions.
     *
     * @param Collection $collection
     */
    public function filterModelCollectionForSecurity(Collection $collection)
    {

    }
}