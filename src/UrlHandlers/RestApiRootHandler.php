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

use Rhubarb\RestApi\Resources\RestResource;

class RestApiRootHandler extends RestResourceHandler
{
    private $roots = [];

    public function setUrl($url)
    {
        // setUrl is called during handler registration.
        parent::setUrl($url);

        // Scan all our children and make sure they are known as root collections.
        foreach ($this->childUrlHandlers as $childHandler) {
            if ($childHandler instanceof RestCollectionHandler || $childHandler instanceof RestResourceHandler) {

                // Register this handler to make sure it's url is known
                $this->roots[ltrim($childHandler->getRestResourceClassName(), '\\')] = $url . $childHandler->getUrl();
            }
        }
    }

    public function getCanonicalUrlForResource(RestResource $resource)
    {
        $class = ltrim(get_class($resource), '\\');

        if (isset($this->roots[$class])) {
            return $this->roots[$class];
        }

        return false;
    }
}