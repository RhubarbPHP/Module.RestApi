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

require_once __DIR__ . '/ItemRestResource.php';

use Rhubarb\RestApi\Exceptions\RestImplementationException;
use Rhubarb\RestApi\Exceptions\UpdateException;
use Rhubarb\RestApi\UrlHandlers\RestHandler;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\SolutionSchema;

/**
 * An ApiResource that wraps a business model and provides some of the heavy lifting.
 */
abstract class ModelItemRestResource extends ItemRestResource
{

    public function __construct($resourceIdentifier = null, $parentResource = null)
    {
        parent::__construct($resourceIdentifier, $parentResource);
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

                        $navigationResource = ModelCollectionRestResource::getRestResourceForModel($navigationValue);

                        if ($navigationResource === false) {
                            throw new RestImplementationException(print_r($navigationValue, true));
                            continue;
                        }
                    }

                    if ($navigationValue instanceof Collection) {
                        $navigationResource = ModelCollectionRestResource::getRestResourceForModelName(SolutionSchema::getModelNameFromClass($navigationValue->getModelClassName()));

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
                                    $ourHref = $this->getRelativeUrl($_SERVER["SCRIPT_NAME"]);

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

        $data = $this->getModelAsResource($this->getSummaryColumns());

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
     * @return Model|null
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

}