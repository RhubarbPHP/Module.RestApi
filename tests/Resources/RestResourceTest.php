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

namespace Rhubarb\RestApi\Tests\Resources;

use Rhubarb\Crown\Context;
use Rhubarb\Crown\Request\JsonRequest;
use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\RestApi\UrlHandlers\RestCollectionHandler;

class RestResourceTest extends RhubarbTestCase
{
    public function testRestPayloadValidationForModelResources()
    {
        include_once(__DIR__ . "/ModelRestResourceTest.php");

        $request = new JsonRequest();
        $request->Server("HTTP_ACCEPT", "application/json");
        $request->Server("REQUEST_METHOD", "post");
        $request->UrlPath = "/contacts/";

        $context = new Context();
        $context->Request = $request;
        $context->SimulatedRequestBody = null;

        $rest = new RestCollectionHandler(__NAMESPACE__ . "\UnitTestExampleRestResource");
        $rest->setUrl("/contacts/");

        $response = $rest->GenerateResponse($request);
        $content = $response->GetContent();

        $this->assertFalse($content->result->status, "POST requests with no payload should fail");

        $stdClass = new \stdClass();
        $stdClass->a = "b";

        $context->SimulatedRequestBody = json_encode($stdClass);

        $response = $rest->GenerateResponse($request);
        $content = $response->GetContent();

        $this->assertEquals("", $content->Forename, "Posting to this collection should return the new resource.");

        $context->SimulatedRequestBody = "";
    }
}
