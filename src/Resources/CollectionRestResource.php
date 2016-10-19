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

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\Crown\Request\Request;
use Rhubarb\RestApi\Exceptions\RestImplementationException;
use Rhubarb\Stem\Collections\Collection;

/**
 * A resource representing a collection of other resources.
 */
abstract class CollectionRestResource extends RestResource
{
    protected $maximumCollectionSize = 100;

    public function __construct(RestResource $parentResource = null)
    {
        parent::__construct($parentResource);
    }

    /**
     * Returns the ItemRestResource for the $resourceIdentifier contained in this collection.
     *
     * @param $resourceIdentifier
     * @return ItemRestResource
     * @throws RestImplementationException Thrown if the item could not be found.
     */
    protected abstract function createItemResource($resourceIdentifier);

    public final function getItemResource($resourceIdentifier)
    {
        $resource = $this->createItemResource($resourceIdentifier);
        $resource->setUrlHandler($this->urlHandler);

        return $resource;
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
        $items = $this->getItems(0, false);

        foreach ($items[0] as $item) {
            if ($item->_id = $resourceIdentifier) {
                return true;
            }
        }

        return false;
    }

    protected function getResourceName()
    {
        return str_replace("Resource", "", basename(str_replace("\\", "/", get_class($this))));
    }

    protected function listItems($asSummary = false)
    {
        Log::performance("Building GET response", "RESTAPI");

        $request = Request::current();

        $rangeHeader = $request->server("HTTP_RANGE");

        $rangeStart = 0;
        $rangeEnd = $this->maximumCollectionSize === false ? false : $this->maximumCollectionSize - 1;

        if ($rangeHeader) {
            $rangeHeader = str_replace("resources=", "", $rangeHeader);
            $rangeHeader = str_replace("bytes=", "", $rangeHeader);

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
                if ($rangeEnd === false) {
                    $rangeEnd = $toText;
                } else {
                    $rangeEnd = min($toText, $rangeStart + ($this->maximumCollectionSize - 1));
                }
            }
        }

        $since = null;

        if ($request->header("If-Modified-Since") != "") {
            $since = new RhubarbDateTime($request->header("If-Modified-Since"));
        }

        Log::performance("Getting items for collection", "RESTAPI");

        list($items, $count) = $asSummary ?
            $this->summarizeItems($rangeStart, $rangeEnd, $since) :
            $this->getItems($rangeStart, $rangeEnd, $since);

        Log::performance("Wrapping GET response", "RESTAPI");

        return $this->createCollectionResourceForItems($items, $rangeStart, min($rangeEnd, ($count <= 0 ? 0 : $count - 1 )), $count);
    }

    /**
     * Creates a valid collection response from a list of objects.
     *
     * @param Collection|\stdClass[] $items
     * @param int $from
     * @param int $to
     * @param int $count
     * @return \stdClass
     */
    protected function createCollectionResourceForItems($items, $from, $to, $count)
    {
        $resource = parent::get();
        $resource->items = $items;
        $resource->count = $count;
        $resource->range = new \stdClass();
        $resource->range->from = $from;
        $resource->range->to = $to;

        return $resource;
    }

    public function summary()
    {
        return $this->listItems(true);
    }

    public function get()
    {
        return $this->listItems();
    }

    /**
     * Implement getItems to return the items for the collection.
     *
     * @param int $rangeStart First index of the items to be returned
     * @param int|bool $rangeEnd Last index. False if the items should not be limited
     * @param RhubarbDateTime $since Optionally a date and time to filter the items for those modified since.
     * @return array    Return a two item array, the first is the items within the range. The second is the overall
     *                    number of items available
     */
    protected function getItems($rangeStart, $rangeEnd, RhubarbDateTime $since = null)
    {
        return [[], 0];
    }

    /**
     * Implement getItems to return the items as a summary for the collection.
     *
     * @param int $rangeStart First index of the items to be returned
     * @param int|bool $rangeEnd Last index. False if the items should not be limited
     * @param RhubarbDateTime $since Optionally a date and time to filter the items for those modified since.
     * @return array    Return a two item array, the first is the items within the range. The second is the overall
     *                    number of items available
     */
    protected function summarizeItems($rangeStart, $rangeEnd, RhubarbDateTime $since = null)
    {
        return [[], 0];
    }
}
