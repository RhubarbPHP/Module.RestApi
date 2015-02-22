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
use Rhubarb\RestApi\Exceptions\RestRequestPayloadValidationException;
use Rhubarb\RestApi\Resources\RestResource;
use Rhubarb\RestApi\UrlHandlers\RestHandler;
use Rhubarb\RestApi\UrlHandlers\RestResourceHandler;
use Rhubarb\Crown\Tests\RhubarbTestCase;

class RestResourceHandlerTest extends RhubarbTestCase
{
    public function testHandlerGetsResource()
    {
        $restHandler = new RestResourceHandler("Rhubarb\RestApi\Tests\Fixtures\UnitTestingRestResource");

        $request = new WebRequest();
        $request->Server("HTTP_ACCEPT", "application/json");
        $request->Server("REQUEST_METHOD", "get");
        $request->UrlPath = "/anything/test";

        $restHandler->setUrl("/anything/test");

        $response = $restHandler->GenerateResponse($request);
        $content = $response->GetContent();

        $this->assertEquals("constructed", $content->value, "The rest handler is not instantiating the resource");
    }

    public function testValidationOfPayloads()
    {
        $restHandler = new RestResourceHandler("Rhubarb\RestApi\Tests\UrlHandlers\ValidatedPayloadTestRestResource", [],
            ["post"]);

        $request = new WebRequest();
        $request->Header("HTTP_ACCEPT", "application/json");
        $request->Server("REQUEST_METHOD", "post");
        $request->UrlPath = "/anything/test";

        $restHandler->setUrl("/anything/test");

        $response = $restHandler->GenerateResponse($request);
        $content = $response->GetContent();

        $this->assertFalse($content->result->status);
        $this->assertEquals("The request payload isn't valid", $content->result->message);
    }
}

class ValidatedPayloadTestRestResource extends RestResource
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