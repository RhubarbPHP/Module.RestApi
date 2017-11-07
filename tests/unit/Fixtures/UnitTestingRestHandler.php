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

use Rhubarb\Crown\Request\Request;
use Rhubarb\RestApi\UrlHandlers\RestHandler;

class UnitTestingRestHandler extends RestHandler
{
    public $getHtml = false;

    public $postHtml = false;

    public $putHtml = false;

//    public $getJson = false;
//
//    public $postJson = false;
//
//    public $putJson = false;

    protected function getSupportedHttpMethods()
    {
        return ["get", "post", "put"];
    }

    protected function getSupportedMimeTypes()
    {
        return ["text/html" => "html", "application/json" => "json"];
    }

    protected function handleGet()
    {
        return $this->getHtml = true;
    }

    protected function handlePost()
    {
        return $this->postHtml = true;
    }

    protected function handlePut()
    {
        $this->putHtml = true;
    }

//    protected function getJson()
//    {
//        $this->getJson = true;
//    }
//
//    protected function postJson()
//    {
//        $this->postJson = true;
//    }


    /**
     * Should be implemented to return a true or false as to whether this handler supports the given request.
     *
     * Normally this involves testing the request URI.
     *
     * @param Request $request
     * @param string $currentUrlFragment
     * @return bool
     */
    protected function getMatchingUrlFragment(Request $request, $currentUrlFragment = "")
    {
        return true;
    }
}
