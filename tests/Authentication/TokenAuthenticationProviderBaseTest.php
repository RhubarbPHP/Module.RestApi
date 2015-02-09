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
use Rhubarb\RestApi\Authentication\AuthenticationProvider;
use Rhubarb\RestApi\Authentication\TokenAuthenticationProviderBase;
use Rhubarb\RestApi\Resources\RestResource;
use Rhubarb\RestApi\UrlHandlers\RestResourceHandler;
use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Schema\SolutionSchema;

class TokenAuthenticationProviderBaseTest extends RhubarbTestCase
{
    protected function setUp()
    {
        parent::setUp();

        AuthenticationProvider::setDefaultAuthenticationProviderClassName("\Rhubarb\RestApi\Tests\Authentication\TokenAuthenticationTestAuthenticationProvider");

        SolutionSchema::registerSchema( "restapi", '\Rhubarb\Stem\Tests\Fixtures\UnitTestingSolutionSchema' );
    }

    public function testTokenRequested()
    {
        $request = new WebRequest();
        $request->Server("HTTP_ACCEPT", "application/json");
        $request->Server("REQUEST_METHOD", "get");
        $request->UrlPath = "/contacts/";

        $rest = new RestResourceHandler(__NAMESPACE__ . "\TokenAuthenticationTestResource");
        $rest->setUrl("/contacts/");

        $response = $rest->GenerateResponse($request);
        $headers = $response->GetHeaders();

        $this->assertArrayHasKey("WWW-authenticate", $headers);

        $request->Header("Authorization", "Token token=\"abc123\"");

        $response = $rest->GenerateResponse($request);
        $headers = $response->GetHeaders();

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

class TokenAuthenticationTestResource extends RestResource
{

}

