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

use Rhubarb\Crown\Application;
use Rhubarb\Crown\Request\JsonRequest;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\RestApi\UrlHandlers\RestCollectionHandler;

class RestResourceTest extends RhubarbTestCase
{
    public function testRestPayloadValidationForModelResources()
    {
        include_once(__DIR__ . "/ModelRestResourceTest.php");

        $request = new JsonRequest();
        $request->serverData['HTTP_ACCEPT'] = "application/json";
        $request->serverData['REQUEST_METHOD'] = "post";
        $request->urlPath = "/contacts/";

        $application = Application::current();
        $application->setCurrentRequest($request);
        $context = $application->context();
        $context->simulatedRequestBody = null;

        $rest = new RestCollectionHandler(UnitTestExampleRestResource::class);
        $rest->setUrl("/contacts/");

        $response = $rest->generateResponse($request);
        $content = $response->getContent();

        $this->assertFalse($content->result->status, "POST requests with no payload should fail");

        $stdClass = new \stdClass();
        $stdClass->a = "b";

        $context->simulatedRequestBody = json_encode($stdClass);

        $response = $rest->generateResponse($request);
        $content = $response->getContent();

        $this->assertEquals("", $content->Forename, "Posting to this collection should return the new resource.");

        $context->simulatedRequestBody = "";
    }
}
