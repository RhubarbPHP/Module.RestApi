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

use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\UrlHandlers\CollectionUrlHandling;
use Rhubarb\RestApi\Exceptions\RestImplementationException;
use Rhubarb\RestApi\Exceptions\RestResourceNotFoundException;
use Rhubarb\RestApi\Resources\CollectionRestResource;

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

    public function __construct($collectionClassName, $childUrlHandlers = [], $supportedHttpMethods = null)
    {
        $this->supportedHttpMethods = ["get", "post", "put", "head", "delete"];

        parent::__construct($collectionClassName, $childUrlHandlers, $supportedHttpMethods);
    }

    protected function getMatchingUrlFragment(Request $request, $currentUrlFragment = "")
    {
        // Overrides the version of this function supplied by CollectionUrlHandling to remove the preference
        // for gobbling up trailing slashes in parent url handlers.

        $uri = $currentUrlFragment;

        $this->matchedUrl = $this->url;

        if (preg_match("|^" . $this->url . "/?([^/]+)/?|", $uri, $match)) {

            $childUrls = [];

            foreach ($this->childUrlHandlers as $child) {
                $childUrls[] = $child->getUrl();
            }

            // Check the matched item is not actually a child handler - let's not extract this as a resource
            // identifier if it is.
            if (!in_array($match[1], $childUrls) &&
                !in_array("/" . $match[1], $childUrls)
            ) {
                $this->resourceIdentifier = $match[1];
                $this->isCollection = false;

                $this->matchedUrl = rtrim($match[0], "/");
            }
        }

        return $this->matchedUrl;
    }

    protected function getRestResource()
    {
        $parentResource = $this->getParentResource();

        if ($parentResource !== null) {
            $childResource = $parentResource->getChildResource($this->matchingUrl);
            if ($childResource) {
                $childResource->setUrlHandler($this);
                return $childResource;
            }
        }

        // We will either be returning a resource or a collection.
        // However even if returning a resource, we first need to instantiate the collection
        // to verify the resource is one of the items in the collection, in case it has been
        // filtered for security reasons.
        $class = $this->apiResourceClassName;

        /**
         * @var CollectionRestResource $resource
         */
        $resource = new $class($this->getParentResource());
        $resource->setInvokedByUrl(true);
        $resource->setUrlHandler($this);

        if ($this->isCollection()) {
            return $resource;
        } else {
            if (!$this->resourceIdentifier) {
                throw new RestImplementationException("The resource identifier for was invalid.");
            }

            try {
                // The api resource attached to a collection url handler can be either an ItemRestResource or
                // a CollectionRestResource. At this point we need an ItemRestResource so if we have a collection
                // we need to ask it for the item.
                if ($resource instanceof CollectionRestResource) {
                    $itemResource = $resource->getItemResource($this->resourceIdentifier);
                } else {
                    $itemResource = new $class($this->resourceIdentifier);
                }

                $itemResource->setUrlHandler($this);
                $itemResource->setInvokedByUrl(true);
                return $itemResource;
            } catch (RestResourceNotFoundException $er) {
                throw $er;
            } catch (RestImplementationException $er) {
                throw new RestImplementationException("That resource identifier does not exist in the collection.");
            }
        }
    }
}
