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

require_once __DIR__ . '/CollectionRestResource.php';

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\RestApi\Exceptions\InsertException;
use Rhubarb\RestApi\Exceptions\RestImplementationException;
use Rhubarb\RestApi\Exceptions\RestResourceNotFoundException;
use Rhubarb\RestApi\Exceptions\UpdateException;
use Rhubarb\RestApi\UrlHandlers\RestApiRootHandler;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\SolutionSchema;

/**
 * A RestResource that understands how to handle collections and items when linked to Stem model.
 */
abstract class ModelRestResource extends CollectionRestResource
{
    /**
     * A list of model names to model resource class names.
     *
     * @var string[]
     */
    public static $modelToResourceMapping = [];

    /**
     * @var bool|Model
     */
    protected $model = false;

    /**
     * If set then the collection has been determined by a parent.
     *
     * @var Collection
     */
    protected $collection = null;

    public function __construct(RestResource $parentResource = null)
    {
        parent::__construct($parentResource);
    }

    /**
     * Turns a model into a flat array structure ready for returning as a resource response.
     *
     * @param string[] $columns The columns to extract
     * @return array A key/value pairing of columns and values
     * @throws RestImplementationException
     */
    protected function transformModelToArray($columns)
    {
        $model = $this->getModel();

        $extract = [];

        $relationships = null;

        foreach ($columns as $label => $column) {

            $columnModel = $model;

            $modifier = "";
            $urlSuffix = false;

            $apiLabel = (is_numeric($label)) ? $column : $label;

            if (is_callable($column)) {
                $value = $column();
            } else {
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
                }

                if (stripos($column, ".") !== false) {
                    $parts = explode(".", $column, 2);

                    $column = $parts[0];
                    $columnModel = $columnModel->$column;

                    $column = $parts[1];

                    if (is_numeric($label)) {
                        $apiLabel = $parts[1];
                    }
                }

                if ($columnModel) {
                    $value = $columnModel->$column;
                } else {
                    $value = "";
                }
            }

            if (is_object($value)) {
                // We can't pass objects back through the API! Let's get a JSON friendly structure instead.
                if (!($value instanceof Model) && !($value instanceof Collection)) {
                    // This seems strange however if we just used json_encode we'd be passing the encoded version
                    // back as a string. We decode to get the original structure back again.
                    $value = json_decode(json_encode($value));
                } else {
                    $navigationResource = false;
                    $navigationResourceIsCollection = false;

                    if ($value instanceof Model) {

                        $navigationResource = $this->getRestResourceForModel($value);

                        if ($navigationResource === false) {
                            throw new RestImplementationException(print_r($value, true));
                            continue;
                        }
                    }

                    if ($value instanceof Collection) {
                        $navigationResource = $this->getRestResourceForModelName(SolutionSchema::getModelNameFromClass($value->getModelClassName()));

                        if ($navigationResource === false) {
                            continue;
                        }

                        $navigationResourceIsCollection = true;
                        $navigationResource->setModelCollection($value);
                    }

                    if ($navigationResource) {
                        switch ($modifier) {
                            case "summary":
                                $value = $navigationResource->summary();
                                break;
                            case "link":
                                $link = $navigationResource->link();

                                if (!isset($link->href) || $navigationResourceIsCollection) {

                                    if (!$urlSuffix) {
                                        throw new RestImplementationException("No canonical URL for " . get_class($navigationResource) . " and no URL suffix supplied for property " . $apiLabel);
                                    }

                                    $ourHref = $this->getHref();

                                    // Override the href with this appendage instead.
                                    $link->href = $ourHref . $urlSuffix;
                                }

                                $value = $link;

                                break;
                            default:
                                $value = $navigationResource->get();
                                break;
                        }
                    }
                }
            }

            if ($value !== null) {
                $extract[$apiLabel] = $value;
            }
        }

        return $extract;
    }

    /**
     * Override to control the columns returned in HEAD requests
     *
     * @return string[]
     */
    protected function getSummaryColumns()
    {
        $columns = [];

        $model = $this->getSampleModel();
        $columnName = $model->getLabelColumnName();

        if ($columnName != "") {
            $columns[] = $columnName;
        }

        return $columns;
    }

    /**
     * Override to control the columns returned in GET requests
     *
     * @return string[]
     */
    protected function getColumns()
    {
        return $this->getSummaryColumns();
    }

    public function summary()
    {
        $resource = $this->getSkeleton();

        $data = $this->transformModelToArray($this->getSummaryColumns());

        foreach ($data as $key => $value) {
            $resource->$key = $value;
        }

        return $resource;
    }

    public function get()
    {
        if (!$this->model) {
            return parent::get();
        }

        $resource = $this->getSkeleton();

        $data = $this->transformModelToArray($this->getColumns());

        foreach ($data as $key => $value) {
            $resource->$key = $value;
        }

        return $resource;
    }

    public function head()
    {
        if (!$this->model) {
            return parent::get();
        }

        $resource = $this->getSkeleton();

        $data = $this->transformModelToArray($this->getSummaryColumns());

        foreach ($data as $key => $value) {
            $resource->resource->$key = $value;
        }

        return $resource;
    }

    /**
     * Gets the Model object used to populate the REST resource
     *
     * This is public as it is sometimes required by child handlers to check security etc.
     *
     * @throws \Rhubarb\RestApi\Exceptions\RestImplementationException
     * @return Model|null
     */
    public function getModel()
    {
        if (!$this->model) {
            throw new RestImplementationException("There is no matching resource for this url");
        }

        return $this->model;
    }

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
    }

    /**
     * Called by a parent resource to pass the child resource a direct list of items for the collection
     *
     * @param Collection $collection
     */
    protected function setModelCollection(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Override to response to the event of a model being updated through a PUT before the model is saved
     *
     * @param $model
     * @param $restResource
     */
    protected function beforeModelUpdated($model, $restResource)
    {

    }

    /**
     * Override to response to the event of a model being updated through a PUT after the model is saved
     *
     * @param $model
     * @param $restResource
     */
    protected function afterModelUpdated($model, $restResource)
    {

    }

    protected function getSkeleton()
    {
        $skeleton = parent::getSkeleton();

        if ($this->model) {
            $skeleton->_id = $this->model->UniqueIdentifier;
        }

        return $skeleton;
    }

    public function put($restResource)
    {
        try {
            $model = $this->getModel();
            $model->importData($restResource);

            $this->beforeModelUpdated($model, $restResource);

            $model->save();

            $this->afterModelUpdated($model, $restResource);

            return true;
        } catch (RecordNotFoundException $er) {
            throw new UpdateException("That record could not be found.");
        } catch (\Exception $er) {
            throw new UpdateException($er->getMessage());
        }
    }

    public function delete()
    {
        try {
            $model = $this->getModel();
            $model->delete();

            return true;
        } catch (\Exception $er) {
            return false;
        }
    }

    public static function registerModelToResourceMapping($modelName, $resourceClassName)
    {
        self::$modelToResourceMapping[$modelName] = $resourceClassName;
    }

    public function getRestResourceForModel(Model $model)
    {
        $modelName = $model->getModelName();

        if (!isset(self::$modelToResourceMapping[$modelName])) {
            throw new RestImplementationException("The model $modelName does not have an associated rest resource.");
        }

        $class = self::$modelToResourceMapping[$modelName];

        /** @var RestResource $resource */
        $resource = new $class();
        $resource->setUrlHandler($this->urlHandler);

        if ($resource instanceof ModelRestResource) {
            /** @var ModelRestResource $resource */
            $resource = $resource->getItemResourceForModel($model);
        }

        return $resource;
    }

    /**
     * @param string $modelName
     * @return bool|ModelRestResource
     */
    public function getRestResourceForModelName($modelName)
    {
        if (!isset(self::$modelToResourceMapping[$modelName])) {
            return false;
        }

        $class = self::$modelToResourceMapping[$modelName];

        /** @var ModelRestResource $resource */
        $resource = new $class();
        $resource->setUrlHandler($this->urlHandler);
        return $resource;
    }

    public static function clearRestResourceMapping()
    {
        self::$modelToResourceMapping = [];
    }

    protected function getSampleModel()
    {
        return SolutionSchema::getModel($this->getModelName());
    }

    /**
     * @return \Rhubarb\Stem\Collections\Collection|null
     */
    public function getModelCollection()
    {
        if ($this->collection) {
            return $this->collection;
        }

        $collection = $this->createModelCollection();

        Log::performance("Filtering collection", "RESTAPI");

        $this->filterModelCollectionAsContainer($collection);
        $this->filterModelCollectionForSecurity($collection);
        $this->filterModelCollectionForQueries($collection);

        if ($this->parentResource instanceof ModelRestResource) {
            $this->parentResource->filterModelCollectionAsContainer($collection);
        }

        return $collection;
    }

    /**
     * Override to filter a model collection to apply any necessary filters only when this is the specific resource being fetched
     *
     * @param Collection $collection
     */
    public function filterModelCollectionForQueries(Collection $collection)
    {

    }

    /**
     * Override to filter a model collection to apply any necessary filters only when this is the REST collection of the specific resource being fetched
     *
     * @param Collection $collection
     */
    public function filterModelCollectionAsContainer(Collection $collection)
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

    /**
     * Returns the name of the model to use for this resource.
     *
     * @return string
     */
    public abstract function getModelName();

    protected function createModelCollection()
    {
        return new Collection($this->getModelName());
    }

    public function containsResourceIdentifier($resourceIdentifier)
    {
        $collection = clone $this->getModelCollection();

        $this->filterModelCollectionAsContainer($collection);

        $collection->filter(new Equals($collection->getModelSchema()->uniqueIdentifierColumnName, $resourceIdentifier));

        if (count($collection) > 0) {
            return true;
        }

        return false;
    }

    protected function summarizeItems($rangeStart, $rangeEnd, RhubarbDateTime $since = null)
    {
        return $this->fetchItems($rangeStart, $rangeEnd, $since, true);
    }

    protected function getItems($rangeStart, $rangeEnd, RhubarbDateTime $since = null)
    {
        return $this->fetchItems($rangeStart, $rangeEnd, $since);
    }

    private function fetchItems($rangeStart, $rangeEnd, RhubarbDateTime $since = null, $asSummary = false)
    {
        $collection = $this->getModelCollection();

        if ($since !== null) {
            $this->filterModelCollectionForModifiedSince($collection, $since);
        }

        $collectionSize = count($collection);
        if ($rangeStart > 0 || $rangeEnd !== false) {
            if ($rangeEnd === false) {
                $pageSize = $collectionSize - $rangeStart;
            } else {
                $pageSize = ($rangeEnd - $rangeStart) + 1;
            }
            $collection->setRange($rangeStart, min($pageSize, $collectionSize));
        }

        $items = [];

        Log::performance("Starting collection iteration", "RESTAPI");

        foreach ($collection as $model) {
            $resource = $this->getItemResourceForModel($model);

            $modelStructure = ($asSummary) ? $resource->summary() : $resource->get();
            $items[] = $modelStructure;
        }

        return [$items, $collectionSize];
    }

    private function getItemResourceForModel($model)
    {
        $resource = clone $this;
        $resource->parentResource = $this;
        $resource->setModel($model);

        return $resource;
    }

    protected function getHref()
    {
        $handler = $this->urlHandler->getParentHandler();

        $root = false;

        // If we have a canonical URL due to a root registration we should give that
        // in preference to the current URL.
        if ($handler instanceof RestApiRootHandler) {
            $root = $handler->getCanonicalUrlForResource($this);
        }

        if (!$root && !$this->invokedByUrl) {
            return false;
        }

        $root = $this->urlHandler->getUrl();

        if ($this->model) {
            return $root . "/" . $this->model->UniqueIdentifier;
        }

        return $root;
    }

    public function post($restResource)
    {
        try {
            $newModel = SolutionSchema::getModel($this->getModelName());

            if (is_array($restResource)) {
                $newModel->importData($restResource);
            }
            $this->beforeModelCreated($newModel, $restResource);

            $newModel->save();
            $this->model = $newModel;

            $this->afterModelCreated($newModel, $restResource);

            return $this->getItemResourceForModel($newModel)->get();
        } catch (RecordNotFoundException $er) {
            throw new InsertException("That record could not be found.");
        } catch (\Exception $er) {
            throw new InsertException($er->getMessage());
        }
    }

    /**
     * Override to respond to the event of a new model being created through a POST
     *
     * @param $model
     * @param $restResource
     */
    protected function afterModelCreated($model, $restResource)
    {

    }

    /**
     * Override to perform additional actions on a model before save, eg setup required relationships from parent resources.
     * Called when data has been imported into $model from $restResource, but before the model is saved.
     *
     * @param Model $model
     * @param $restResource
     */
    protected function beforeModelCreated($model, $restResource)
    {

    }

    /**
     * Returns the ItemRestResource for the $resourceIdentifier contained in this collection.
     *
     * @param $resourceIdentifier
     * @return ItemRestResource
     * @throws RestImplementationException Thrown if the item could not be found.
     */
    public function createItemResource($resourceIdentifier)
    {
        try {
            $model = SolutionSchema::getModel($this->getModelName(), $resourceIdentifier);
        } catch (RecordNotFoundException $er) {
            throw new RestResourceNotFoundException(self::class, $resourceIdentifier);
        }

        return $this->getItemResourceForModel($model);
    }
}
