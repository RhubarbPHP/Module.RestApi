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

namespace Rhubarb\RestApi\Tests\Fixtures;

use Rhubarb\RestApi\Exceptions\RestImplementationException;
use Rhubarb\RestApi\Resources\CollectionRestResource;
use Rhubarb\RestApi\Resources\ItemRestResource;
use Rhubarb\RestApi\UrlHandlers\RestHandler;

class UnitTestingRestResource extends CollectionRestResource
{
    public function get(RestHandler $handler = null)
    {
        $resource = parent::get();
        $resource->value = "collection";

        return $resource;
    }

    /**
     * Returns the ItemRestResource for the $resourceIdentifier contained in this collection.
     *
     * @param $resourceIdentifier
     * @return ItemRestResource
     * @throws RestImplementationException Thrown if the item could not be found.
     */
    public function getItemResource($resourceIdentifier)
    {
        $resource = new \stdClass();
        $resource->_id = 1;
        $resource->value = "constructed";

        return new UnitTestingConstructedRestResource( $resource );
    }
}