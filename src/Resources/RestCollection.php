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

require_once __DIR__ . '/RestResource.php';

use Rhubarb\Crown\Context;
use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\RestApi\UrlHandlers\RestHandler;

/**
 * A resource representing a collection of other resources.
 */
class RestCollection extends RestResource
{
    protected $restResource = "";

    protected $maximumCollectionSize = 100;

    public function __construct($restResource, RestResource $parentResource = null)
    {
        $this->restResource = $restResource;

        parent::__construct(null, $parentResource);
    }

    /**
     * Test to see if the given resource identifier exists in the collection of resources.
     *
     * @param $resourceIdentifier
     * @return True if it exists, false if it does not.
     */
    public function containsResourceIdentifier($resourceIdentifier)
    {
        // This will be very slow however the base implementation can do nothing else.
        // Inheritors of this class should override this if they can do this faster!
        $items = $this->getItems(0, 999999999);

        foreach ($items[0] as $item) {
            if ($item->_id = $resourceIdentifier) {
                return true;
            }
        }

        return false;
    }

    protected function getResourceName()
    {
        return str_replace("Resource", "", basename(str_replace("\\", "/", get_class($this->restResource))));
    }

    public function getHref($nonCanonicalUrlTemplate = "")
    {
        $urlTemplate = RestResource::getCanonicalResourceUrl(get_class($this->restResource));

        if (!$urlTemplate && $nonCanonicalUrlTemplate !== "") {
            $urlTemplate = $nonCanonicalUrlTemplate;
        }

        if ($urlTemplate) {
            $request = Context::currentRequest();

            $urlStub = (($request->Server("SERVER_PORT") == 443) ? "https://" : "http://") .
                $request->Server("HTTP_HOST");

            return $urlStub . $urlTemplate . "/" . $this->id;
        }

        return "";
    }

    private function listItems(RestHandler $handler = null, $asSummary = false)
    {
        $request = Context::currentRequest();

        $rangeHeader = $request->Server("HTTP_RANGE");

        $rangeStart = 0;
        $rangeEnd = $this->maximumCollectionSize - 1;

        if ($rangeHeader) {
            $rangeHeader = str_replace("resources=", "", $rangeHeader);

            $parts = explode("-", $rangeHeader);

            $fromText = false;
            $toText = false;

            if (sizeof($parts) > 0) {
                $fromText = (int)$parts[0];
            }

            if (sizeof($parts) > 1) {
                $toText = (int)$parts[1];
            }

            if ($fromText) {
                $rangeStart = $fromText;
            }

            if ($toText) {
                $rangeEnd = min($toText, $rangeStart + ($this->maximumCollectionSize - 1));
            }
        }

        $since = null;

        if ($request->Header("If-Modified-Since") != "") {
            $since = new RhubarbDateTime($request->Header("If-Modified-Since"));
        }

        list($items, $count) = ($asSummary) ?
            $this->summarizeItems($rangeStart, $rangeEnd, $since) :
            $this->getItems($rangeStart, $rangeEnd, $since);

        return $this->createCollectionResourceForItems($items, $rangeStart, min($rangeEnd, $count - 1), $handler);
    }

    /**
     * Creates a valid collection response from a list of objects.
     *
     * @param $items
     * @param $from
     * @param $to
     * @param $handler
     * @return \stdClass
     */
    protected function createCollectionResourceForItems($items, $from, $to, $handler)
    {
        $resource = parent::get($handler);
        $resource->items = $items;

        $resource->range = new \stdClass();
        $resource->range->from = $from;
        $resource->range->to = $to;

        return $resource;
    }

    public function summary(RestHandler $handler = null)
    {
        return $this->listItems($handler, true);
    }

    public function get(RestHandler $handler = null)
    {
        return $this->listItems($handler);
    }

    /**
     * Implement getItems to return the items for the collection.
     *
     * @param $from
     * @param $to
     * @param RhubarbDateTime $since Optionally a date and time to filter the items for those modified since.
     * @return array    Return a two item array, the first is the items within the range. The second is the overall
     *                    number of items available
     */
    protected function getItems($from, $to, RhubarbDateTime $since = null)
    {
        return [[], 0];
    }

    /**
     * Implement getItems to return the items as a summary for the collection.
     *
     * @param $from
     * @param $to
     * @param RhubarbDateTime $since Optionally a date and time to filter the items for those modified since.
     * @return array    Return a two item array, the first is the items within the range. The second is the overall
     *                    number of items available
     */
    protected function summarizeItems($from, $to, RhubarbDateTime $since = null)
    {
        return [[], 0];
    }

    public function validateRequestPayload($payload, $method)
    {
        /**
         * Collections aren't qualified to answer the question about payload validity
         * We need to ask the actual resource instead.
         */
        $this->restResource->validateRequestPayload($payload, $method);
    }
}