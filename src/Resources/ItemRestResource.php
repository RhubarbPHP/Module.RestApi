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
use Rhubarb\RestApi\UrlHandlers\RestApiRootHandler;
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

    protected function getHref()
    {
        $handler = $this->urlHandler->getParentHandler();

        // If we have a canonical URL due to a root registration we should give that
        // in preference to the current URL.
        if ( $handler instanceof RestApiRootHandler ){
            $href = $handler->getCanonicalUrlForResource($this);

            return $href."/".$this->id;
        }

        if ( $this->invokedByUrl ) {
            return parent::getHref() . "/" . $this->id;
        }

        return false;
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