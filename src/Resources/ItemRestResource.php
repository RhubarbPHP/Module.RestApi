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

use Rhubarb\Crown\Context;
use Rhubarb\RestApi\UrlHandlers\RestHandler;

/**
 * A specific type of RestResource that has an _id property and any number of key value pairs
 */
abstract class ItemRestResource extends RestResource
{
    protected $id;

    public function __construct($resourceIdentifier = null, RestResource $parentResource = null)
    {
        parent::__construct($parentResource);

        $this->setID($resourceIdentifier);
    }

    protected function setID($id)
    {
        $this->id = $id;
    }

    /**
     * Calculates the correct and unique href property for this resource.
     *
     * @param string $nonCanonicalUrlTemplate If this resource has no canonical url template then you can supply one instead.
     * @return string
     */
    /*
    public function getRelativeUrl($nonCanonicalUrlTemplate = "")
    {
        $urlTemplate = RestResource::getCanonicalResourceUrl(get_class($this));

        if (!$urlTemplate && $nonCanonicalUrlTemplate !== "") {
            $urlTemplate = $nonCanonicalUrlTemplate;
        }

        if ($urlTemplate) {
            $request = Context::currentRequest();

            $urlStub = (($request->Server("SERVER_PORT") == 443) ? "https://" : "http://") .
                $request->Server("HTTP_HOST");

            if ($this->id && $urlTemplate[strlen($urlTemplate) - 1] != "/") {
                return $urlStub . $urlTemplate . "/" . $this->id;
            }
        }

        return "";
    }
    */
    protected function getHref()
    {
        return parent::getHref()."/".$this->id;
    }

    protected function getSkeleton()
    {
        $skeleton = parent::getSkeleton();

        if ($this->id) {
            $skeleton->_id = $this->id;
        }

        return $skeleton;
    }
}