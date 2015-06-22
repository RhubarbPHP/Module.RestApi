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

use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\Module;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\RestApi\Exceptions\RestImplementationException;
use Rhubarb\RestApi\Resources\RestResource;
use Rhubarb\RestApi\Tests\Fixtures\UnitTestingRestHandler;
use Rhubarb\RestApi\UrlHandlers\RestHandler;
use Rhubarb\RestApi\UrlHandlers\RestResourceHandler;

class RestHandlerTest extends RhubarbTestCase
{
    /**
     * @var UnitTestingRestHandler
     */
    private $unitTestRestHandler;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        Module::RegisterModule(new UnitTestRestModule());
        Module::InitialiseModules();
    }

    protected function setUp()
    {
        parent::setUp();

        $this->unitTestRestHandler = new UnitTestingRestHandler();
    }

    public function testMethodsCalledCorrectly()
    {
        $request = new WebRequest();

        $request->Header("HTTP_ACCEPT", "image/jpeg");
        $response = $this->unitTestRestHandler->GenerateResponse($request);
        $this->assertFalse($response, "image/jpeg should not be handled by this handler");

        $request->Header("HTTP_ACCEPT", "text/html");
        $request->Server("REQUEST_METHOD", "options");

        try {
            $this->unitTestRestHandler->GenerateResponse($request);
            $this->fail("HTTP OPTIONS should not be handled by this handler");
        } catch (ForceResponseException $er) {
        }

        // Check that */* is treated as text/html
        $request->Header("HTTP_ACCEPT", "*/*");
        $request->Server("REQUEST_METHOD", "get");

        $this->unitTestRestHandler->GenerateResponse($request);
        $this->assertTrue($this->unitTestRestHandler->getHtml);

        $request->Header("HTTP_ACCEPT", "text/html");
        $request->Server("REQUEST_METHOD", "get");

        $this->unitTestRestHandler->GenerateResponse($request);
        $this->assertTrue($this->unitTestRestHandler->getHtml);

        $request->Server("REQUEST_METHOD", "post");

        $this->unitTestRestHandler->GenerateResponse($request);
        $this->assertTrue($this->unitTestRestHandler->postHtml);

        $request->Server("REQUEST_METHOD", "put");

        $this->unitTestRestHandler->GenerateResponse($request);
        $this->assertTrue($this->unitTestRestHandler->putHtml);

        $request->Header("HTTP_ACCEPT", "application/json");
        $request->Server("REQUEST_METHOD", "get");

        $this->unitTestRestHandler->GenerateResponse($request);
        $this->assertTrue($this->unitTestRestHandler->getJson);

        $request->Server("REQUEST_METHOD", "post");

        $this->unitTestRestHandler->GenerateResponse($request);
        $this->assertTrue($this->unitTestRestHandler->postJson);

        $request->Server("REQUEST_METHOD", "put");

        $this->setExpectedException("Rhubarb\RestApi\Exceptions\RestImplementationException");

        $this->unitTestRestHandler->GenerateResponse($request);
    }

    public function testRestHandlerFormatsExceptionsCorrectly()
    {
        $request = new WebRequest();
        $request->UrlPath = "/rest-test/";

        $response = Module::GenerateResponseForRequest($request);

        $this->assertInstanceOf('\Rhubarb\Crown\Response\JsonResponse', $response);

        $this->assertEquals("Sorry, something went wrong and we couldn't complete your request. The developers have
been notified.", $response->GetContent()->result->message);
    }
}

class UnitTestRestModule extends Module
{
    public function __construct()
    {
        $this->namespace = __NAMESPACE__;

        parent::__construct();
    }

    protected function RegisterUrlHandlers()
    {
        $this->AddUrlHandlers(
            [
                "/rest-test/" => $url = new RestResourceHandler('\Rhubarb\RestApi\Tests\UrlHandlers\UnitTestRestExceptionResource')
            ]
        );

        $url->SetPriority(100);
    }
}

class UnitTestRestExceptionResource extends RestResource
{
    public function get(RestHandler $handler = null)
    {
        throw new RestImplementationException("Somethings crashed");
    }
}