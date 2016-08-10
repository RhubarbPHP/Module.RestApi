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

namespace Rhubarb\RestApi\tests\unit\UrlHandlers;

use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\Module;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Response\JsonResponse;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\RestApi\Exceptions\RestImplementationException;
use Rhubarb\RestApi\Resources\ItemRestResource;
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

        Module::registerModule(new UnitTestRestModule());
        Module::initialiseModules();
    }

    protected function setUp()
    {
        parent::setUp();

        $this->unitTestRestHandler = new UnitTestingRestHandler();
    }

    public function testMethodsCalledCorrectly()
    {
        $request = new WebRequest();

        $request->header("HTTP_ACCEPT", "image/jpeg");
        $response = $this->unitTestRestHandler->generateResponse($request);
        $this->assertFalse($response, "image/jpeg should not be handled by this handler");

        $request->header("HTTP_ACCEPT", "text/html");
        $request->server("REQUEST_METHOD", "options");

        try {
            $this->unitTestRestHandler->generateResponse($request);
            $this->fail("HTTP OPTIONS should not be handled by this handler");
        } catch (ForceResponseException $er) {
        }

        // Check that */* is treated as text/html
        $request->header("HTTP_ACCEPT", "*/*");
        $request->server("REQUEST_METHOD", "get");

        $this->unitTestRestHandler->generateResponse($request);
        $this->assertTrue($this->unitTestRestHandler->getHtml);

        $request->header("HTTP_ACCEPT", "text/html");
        $request->server("REQUEST_METHOD", "get");

        $this->unitTestRestHandler->generateResponse($request);
        $this->assertTrue($this->unitTestRestHandler->getHtml);

        $request->server("REQUEST_METHOD", "post");

        $this->unitTestRestHandler->generateResponse($request);
        $this->assertTrue($this->unitTestRestHandler->postHtml);

        $request->server("REQUEST_METHOD", "put");

        $this->unitTestRestHandler->generateResponse($request);
        $this->assertTrue($this->unitTestRestHandler->putHtml);

        $request->header("HTTP_ACCEPT", "application/json");
        $request->server("REQUEST_METHOD", "get");

        $this->unitTestRestHandler->generateResponse($request);
        $this->assertTrue($this->unitTestRestHandler->getJson);

        $request->server("REQUEST_METHOD", "post");

        $this->unitTestRestHandler->generateResponse($request);
        $this->assertTrue($this->unitTestRestHandler->postJson);

        $request->server("REQUEST_METHOD", "put");

        $this->setExpectedException(RestImplementationException::class);

        $this->unitTestRestHandler->generateResponse($request);
    }

    public function testRestHandlerFormatsExceptionsCorrectly()
    {
        $request = new WebRequest();
        $request->UrlPath = "/rest-test/";

        $response = Module::generateResponseForRequest($request);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $this->assertEquals("Sorry, something went wrong and we couldn't complete your request. The developers have been notified.",
            str_replace(["\r\n", "\n"], " ", $response->getContent()->result->message));
    }
}

class UnitTestRestModule extends Module
{
    public function __construct()
    {
        $this->namespace = __NAMESPACE__;

        parent::__construct();
    }

    protected function registerUrlHandlers()
    {
        $this->addUrlHandlers(
            [
                "/rest-test/" => $url = new RestResourceHandler(UnitTestRestExceptionResource::class)
            ]
        );

        $url->setPriority(100);
    }
}

class UnitTestRestExceptionResource extends ItemRestResource
{
    public function get(RestHandler $handler = null)
    {
        throw new RestImplementationException("Something's crashed");
    }
}
