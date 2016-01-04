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

use Rhubarb\Crown\Encryption\HashProvider;
use Rhubarb\Crown\Encryption\PlainTextHashProvider;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\RestApi\Authentication\AuthenticationProvider;
use Rhubarb\RestApi\Authentication\ModelLoginProviderAuthenticationProvider;
use Rhubarb\RestApi\Resources\ItemRestResource;
use Rhubarb\RestApi\UrlHandlers\RestHandler;
use Rhubarb\RestApi\UrlHandlers\RestResourceHandler;
use Rhubarb\Stem\LoginProviders\ModelLoginProvider;
use Rhubarb\Stem\Tests\Fixtures\User;

class LoginProviderRestAuthenticationProviderTest extends RhubarbTestCase
{
    protected function setUp()
    {
        parent::setUp();

        User::ClearObjectCache();

        HashProvider::SetHashProviderClassName(PlainTextHashProvider::class);

        AuthenticationProvider::setDefaultAuthenticationProviderClassName(UnitTestLoginProviderRestAuthenticationProvider::class);

        $user = new User();
        $user->Username = "bob";
        $user->Password = "smith";
        $user->Active = 1;
        $user->Save();
    }

    protected function tearDown()
    {
        parent::tearDown();

        AuthenticationProvider::setDefaultAuthenticationProviderClassName("");
    }

    public function testAuthenticationProviderWorks()
    {
        $request = new WebRequest();
        $request->Server("HTTP_ACCEPT", "application/json");
        $request->Server("REQUEST_METHOD", "get");
        $request->UrlPath = "/contacts/";

        $rest = new RestResourceHandler(RestAuthenticationTestResource::class);
        $rest->setUrl("/contacts/");

        $response = $rest->GenerateResponse($request);
        $headers = $response->GetHeaders();

        $this->assertArrayHasKey("WWW-authenticate", $headers);

        $this->assertContains("Basic", $headers["WWW-authenticate"]);
        $this->assertContains("realm=\"API\"", $headers["WWW-authenticate"]);

        // Supply the credentials
        $request->Header("Authorization", "Basic ".base64_encode(base64_encode('bob').':'.base64_encode('smith')));

        $response = $rest->GenerateResponse($request);
        $headers = $response->GetHeaders();

        $content = $response->GetContent();

        $this->assertTrue($content->authenticated);

        // Incorrect credentials.
        $request->Header("Authorization", "Basic " . base64_encode(base64_encode('terry').':'.base64_encode('smith')));

        $response = $rest->GenerateResponse($request);
        $headers = $response->GetHeaders();

        $this->assertArrayHasKey("WWW-authenticate", $headers);
    }
}

class UnitTestLoginProviderRestAuthenticationProvider extends ModelLoginProviderAuthenticationProvider
{
    protected function GetLoginProviderClassName()
    {
        return RestAuthenticationTestLoginProvider::class;
    }
}

class RestAuthenticationTestResource extends ItemRestResource
{
    public function get(RestHandler $handler = null)
    {
        $response = parent::get($handler);
        $response->authenticated = true;

        return $response;
    }
}

class RestAuthenticationTestLoginProvider extends ModelLoginProvider
{
    public function __construct()
    {
        parent::__construct(
            User::class,
            "Username",
            "Password",
            "Active"
        );
    }
}