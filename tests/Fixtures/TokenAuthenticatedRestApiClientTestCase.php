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

namespace Rhubarb\RestApi\Tests\Fixtures;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\RestApi\Clients\RestHttpRequest;
use Rhubarb\RestApi\Clients\TokenAuthenticatedRestClient;

abstract class TokenAuthenticatedRestApiClientTestCase extends RhubarbTestCase
{
    abstract protected function getApiUri();

    abstract protected function getUsername();

    abstract protected function getPassword();

    abstract protected function getTokensUri();

    protected function GetToken()
    {
        return false;
    }

    public function makeApiCall($uri, $method = "get", $payload = null)
    {
        $client = new TokenAuthenticatedRestClient(
            $this->getApiUri(),
            $this->getUsername(),
            $this->getPassword(),
            $this->getTokensUri()
        );

        $token = $this->GetToken();

        if ($token) {
            $client->setToken($token);
        }

        $request = new RestHttpRequest($uri, $method, $payload);
        $response = $client->makeRequest($request);

        return $response;
    }
}