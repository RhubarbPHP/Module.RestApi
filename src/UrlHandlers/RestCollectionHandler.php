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

namespace Rhubarb\RestApi\UrlHandlers;

require_once __DIR__ . '/RestResourceHandler.php';

use Rhubarb\Crown\UrlHandlers\CollectionUrlHandling;
use Rhubarb\RestApi\Exceptions\RestImplementationException;

/**
 * A RestHandler that knows about urls that can point to either a resource or a collection.
 *
 * This handler will try to instantiate an API resource and then call the appropriate action on it.
 * It supports passing through get, post, put, head and delete methods to the resource type and currently
 * works only with JSON responses.
 */
class RestCollectionHandler extends RestResourceHandler
{
    use CollectionUrlHandling;

    public function __construct($restResourceClassName, $childUrlHandlers = [], $supportedHttpMethods = null)
    {
        $this->supportedHttpMethods = ["get", "post", "put", "head", "delete"];

        parent::__construct($restResourceClassName, $childUrlHandlers, $supportedHttpMethods);
    }

    protected function getResource()
    {
        // We will either be returning a resource or a collection.
        // However even if returning a resource, we first need to instantiate the collection
        // to verify the resource is one of the items in the collection, in case it has been
        // filtered for security reasons.
        $class = $this->apiResourceClassName;
        $resource = new $class(null, $this->getParentResource());

        $collection = $resource->getCollection();

        if ($this->isCollection()) {
            return $collection;
        } else {
            if (!$this->resourceIdentifier) {
                throw new RestImplementationException("The resource identifier for was invalid.");
            }

            if (!$collection->containsResourceIdentifier($this->resourceIdentifier)) {
                throw new RestImplementationException("That resource identifier does not exist in the collection.");
            }

            $className = $this->apiResourceClassName;
            $resource = new $className($this->resourceIdentifier, $this->getParentResource());

            return $resource;
        }
    }
}