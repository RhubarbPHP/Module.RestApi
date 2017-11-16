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
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\RestApi\Exceptions\RestRequestPayloadValidationException;
use Rhubarb\RestApi\Resources\ItemRestResource;
use Rhubarb\RestApi\Tests\Fixtures\UnitTestingRestResource;
use Rhubarb\RestApi\UrlHandlers\RestHandler;
use Rhubarb\RestApi\UrlHandlers\RestResourceHandler;

class RestResourceHandlerTest extends RhubarbTestCase
{
    public function testHandlerGetsResource()
    {
        $restHandler = new RestResourceHandler(UnitTestingRestResource::class);

        $request = new WebRequest();
        $request->serverData["accept"] = "application/json";
        $request->serverData["REQUEST_METHOD"] = "get";
        $request->urlPath = "/anything/test";

        $restHandler->setUrl("/anything/test");

        $response = $restHandler->generateResponse($request);
        $content = $response->getContent();

        $this->assertEquals("collection", $content->value, "The rest handler is not instantiating the resource");
    }

    public function testValidationOfPayloads()
    {
        $restHandler = new RestResourceHandler(ValidatedPayloadTestRestResource::class, [], ["post"]);

        $request = new WebRequest();
        $request->headerData["accept"] = "application/json";
        $request->serverData["REQUEST_METHOD"] = "post";
        $request->urlPath = "/anything/test";

        $restHandler->setUrl("/anything/test");

        $response = $restHandler->generateResponse($request);
        $content = $response->getContent();

        $this->assertFalse($content->result->status);
        $this->assertEquals("The request payload isn't valid", $content->result->message);
    }
}

class ValidatedPayloadTestRestResource extends ItemRestResource
{
    public function validateRequestPayload($payload, $method)
    {
        throw new RestRequestPayloadValidationException("The request payload isn't valid");
    }

    public function post($restResource, RestHandler $handler = null)
    {
        // Simply return an empty resource for now.
        return $this->get($handler);
    }

    public function put($restResource, RestHandler $handler = null)
    {
        return $this->post($restResource, $handler);
    }
}
