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

namespace Rhubarb\RestApi\Tests\Authentication;

use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\RestApi\Authentication\AuthenticationProvider;
use Rhubarb\RestApi\Authentication\TokenAuthenticationProviderBase;
use Rhubarb\RestApi\Resources\ItemRestResource;
use Rhubarb\RestApi\UrlHandlers\RestResourceHandler;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\Tests\Fixtures\UnitTestingSolutionSchema;

class TokenAuthenticationProviderBaseTest extends RhubarbTestCase
{
    protected function setUp()
    {
        parent::setUp();

        AuthenticationProvider::setDefaultAuthenticationProviderClassName(TokenAuthenticationTestAuthenticationProvider::class);

        SolutionSchema::registerSchema("restapi", UnitTestingSolutionSchema::class);
    }

    public function testTokenRequested()
    {
        $request = new WebRequest();
        $request->server("HTTP_ACCEPT", "application/json");
        $request->server("REQUEST_METHOD", "get");
        $request->UrlPath = "/contacts/";

        $rest = new RestResourceHandler(TokenAuthenticationTestResource::class);
        $rest->setUrl("/contacts/");

        $response = $rest->generateResponse($request);
        $headers = $response->getHeaders();

        $this->assertArrayHasKey("WWW-authenticate", $headers);

        $request->header("Authorization", "Token token=\"abc123\"");

        $response = $rest->generateResponse($request);
        $headers = $response->getHeaders();

        $this->assertArrayNotHasKey("WWW-authenticate", $headers);
    }

    protected function tearDown()
    {
        parent::tearDown();

        AuthenticationProvider::setDefaultAuthenticationProviderClassName("");
    }
}

class TokenAuthenticationTestAuthenticationProvider extends TokenAuthenticationProviderBase
{
    /**
     * Returns true if the token is valid.
     *
     * @param $tokenString
     * @return mixed
     */
    protected function isTokenValid($tokenString)
    {
        return true;
    }
}

class TokenAuthenticationTestResource extends ItemRestResource
{

}
