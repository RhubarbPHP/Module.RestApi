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

namespace Rhubarb\RestApi\Tests\UrlHandlers;

use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\RestApi\Tests\Fixtures\UnitTestingRestResource;
use Rhubarb\RestApi\UrlHandlers\RestCollectionHandler;

class RestCollectionHandlerTest extends RhubarbTestCase
{
    public function testUrlMatching()
    {
        $request = new WebRequest();
        $request->server("HTTP_ACCEPT", "application/json");
        $request->server("REQUEST_METHOD", "get");

        $rest = new UnitTestRestCollectionHandler();
        $rest->setUrl("/users/");

        $request->UrlPath = "/users/";

        $response = $rest->generateResponse($request);
        $content = $response->getContent();

        $this->assertEquals("collection", $content->value, "The rest handler is not recognising the collection");

        $request->UrlPath = "/users/1/";

        $response = $rest->generateResponse($request);
        $content = $response->getContent();

        $this->assertEquals("constructed", $content->value, "The rest handler is not instantiating the resource");
    }
}

class UnitTestRestCollectionHandler extends RestCollectionHandler
{
    public function __construct($childUrlHandlers = [])
    {
        parent::__construct(UnitTestingRestResource::class, $childUrlHandlers);
    }
}
