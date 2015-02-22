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

require_once __DIR__ . '/RestCollection.php';

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\RestApi\Exceptions\InsertException;
use Rhubarb\RestApi\UrlHandlers\RestHandler;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Schema\SolutionSchema;

class ModelRestCollection extends RestCollection
{
    private $collection = null;

    public function __construct($restResource, RestResource $parentResource = null, Collection $itemsCollection)
    {
        parent::__construct($restResource, $parentResource);

        $this->collection = $itemsCollection;
    }

    public function setModelCollection(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @return \Rhubarb\Stem\Collections\Collection|null
     */
    public function getModelCollection()
    {
        return $this->collection;
    }

    public function containsResourceIdentifier($resourceIdentifier)
    {
        $collection = clone $this->getModelCollection();

        if ($this->restResource instanceof ModelRestResource) {
            $this->restResource->filterModelCollectionContainer($collection);
        }

        $collection->filter(new Equals($collection->getModelSchema()->uniqueIdentifierColumnName, $resourceIdentifier));

        if (count($collection) > 0) {
            return true;
        }

        return false;
    }

    protected function getItems($from, $to, RhubarbDateTime $since = null)
    {
        if ($this->restResource instanceof ModelRestResource) {
            $collection = $this->getModelCollection();

            $this->restResource->filterModelResourceCollection($collection);

            if ($since !== null) {
                $this->restResource->filterModelCollectionForModifiedSince($collection, $since);
            }

            $pageSize = ($to - $from) + 1;
            $collection->setRange($from, $pageSize);

            $items = [];

            foreach ($collection as $model) {
                $this->restResource->setModel($model);

                $modelStructure = $this->restResource->get();
                $items[] = $modelStructure;
            }

            return [$items, sizeof($collection)];
        }

        return parent::getItems($from, $to);
    }


    public function post($restResource, RestHandler $handler = null)
    {
        try {
            $newModel = SolutionSchema::getModel($this->restResource->getModelName());

            if (is_array($restResource)) {
                $newModel->importData($restResource);
            }

            $newModel->save();

            $this->restResource->afterModelCreated($newModel, $restResource);

            $this->restResource->setModel($newModel);

            return $this->restResource->get();
        } catch (RecordNotFoundException $er) {
            throw new InsertException("That record could not be found.");
        } catch (\Exception $er) {
            throw new InsertException($er->getMessage());
        }
    }
}