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
use Rhubarb\Crown\Response\Response;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\RestApi\Authentication\AuthenticationProvider;
use Rhubarb\RestApi\Authentication\ModelLoginProviderAuthenticationProvider;
use Rhubarb\RestApi\Resources\ItemRestResource;
use Rhubarb\RestApi\UrlHandlers\RestHandler;
use Rhubarb\RestApi\UrlHandlers\RestResourceHandler;
use Rhubarb\Stem\LoginProviders\ModelLoginProvider;
use Rhubarb\Stem\Tests\unit\Fixtures\TestExpiredUser;
use Rhubarb\Stem\Tests\unit\Fixtures\User;

class LoginProviderRestAuthenticationProviderTest extends RhubarbTestCase
{
    protected function setUp()
    {
        parent::setUp();

        User::clearObjectCache();

        HashProvider::setProviderClassName(PlainTextHashProvider::class);

        AuthenticationProvider::setProviderClassName(UnitTestLoginProviderRestAuthenticationProvider::class);

        $user = new User();
        $user->Username = "bob";
        $user->Password = "smith";
        $user->Active = 1;
        $user->save();
    }

    protected function tearDown()
    {
        parent::tearDown();

//        AuthenticationProvider::setProviderClassName("");
    }

    public function testAuthenticationProviderWorks()
    {
        $request = new WebRequest();
        $request->serverData["HTTP_ACCEPT"] = "application/json";
        $request->serverData["REQUEST_METHOD"] = "get";
        $request->urlPath = "/contacts/";

        $rest = new RestResourceHandler(RestAuthenticationTestResource::class);
        $rest->setUrl("/contacts/");

        $response = $rest->generateResponse($request);
        $headers = $response->getHeaders();

        $this->assertArrayHasKey("WWW-authenticate", $headers);

        $this->assertContains("Basic", $headers["WWW-authenticate"]);
        $this->assertContains("realm=\"API\"", $headers["WWW-authenticate"]);

        // Supply the credentials
        //  Passing lowercase Authorization header to match the logic ran inside the WebRequest object
        $request->headerData["authorization"] = "Basic " . base64_encode("bob:smith");

        $response = $rest->generateResponse($request);
        $headers = $response->getHeaders();

        $this->assertArrayNotHasKey("WWW-authenticate", $headers);
        $content = $response->getContent();

        $this->assertTrue($content->authenticated);

        // Incorrect credentials.
        $request->headerData["authorization"] = "Basic " . base64_encode("terry:smith");

        $response = $rest->generateResponse($request);
        $headers = $response->getHeaders();

        $this->assertArrayHasKey("WWW-authenticate", $headers);
    }

    public function testExpiredUserWithAuthenticationProvider()
    {
        AuthenticationProvider::setProviderClassName(UnitTestExpiredLoginProviderRestAuthenticationProvider::class);

        $user = new TestExpiredUser();
        $user->Username = "expireduser";
        $user->Password = "password";
        $user->Active = 1;
        $user->save();

        $request = new WebRequest();
        $request->serverData["HTTP_ACCEPT"] = "application/json";
        $request->serverData["REQUEST_METHOD"] = "get";
        $request->urlPath = "/contacts/";

        $rest = new RestResourceHandler(RestAuthenticationTestResource::class);
        $rest->setUrl("/contacts/");

        // Supply the credentials
        //  Passing lowercase Authorization header to match the logic ran inside the WebRequest object
        $request->headerData["authorization"] = "Basic " . base64_encode("expireduser:password");

        $response = $rest->generateResponse($request);
        $headers = $response->getHeaders();

        $this->assertArrayHasKey("WWW-authenticate", $headers);

        $this->assertContains("Basic", $headers["WWW-authenticate"]);
        $this->assertContains("realm=\"API\"", $headers["WWW-authenticate"]);

        $this->assertEquals($response->getResponseCode(), Response::HTTP_STATUS_CLIENT_ERROR_FORBIDDEN);
    }
}

class UnitTestLoginProviderRestAuthenticationProvider extends ModelLoginProviderAuthenticationProvider
{
    protected function getLoginProviderClassName()
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

class UnitTestExpiredLoginProviderRestAuthenticationProvider extends ModelLoginProviderAuthenticationProvider
{
    protected function getLoginProviderClassName()
    {
        return RestAuthenticationExpiredTestLoginProvider::class;
    }
}

class RestAuthenticationExpiredTestLoginProvider extends ModelLoginProvider
{
    public function __construct()
    {
        parent::__construct(
            TestExpiredUser::class,
            "Username",
            "Password",
            "Active"
        );
    }
}
