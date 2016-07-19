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

namespace Rhubarb\RestApi\Clients;

require_once __DIR__ . '/BasicAuthenticatedRestClient.php';

use Rhubarb\Crown\Http\HttpRequest;
use Rhubarb\RestApi\Exceptions\RestAuthenticationException;
use Rhubarb\RestApi\Exceptions\RestImplementationException;

/**
 * Extends the BasicAuthenticatedRestClient by adding support for tokens after the first
 * basic authenticated request to get a new token.
 */
class TokenAuthenticatedRestClient extends BasicAuthenticatedRestClient
{
    protected $tokensUri = "";

    protected $token = "";

    /**
     * @var bool True if the client is busy getting the authentication token.
     */
    protected $gettingToken = false;

    public function __construct($apiUrl, $username, $password, $tokensUri, $existingToken = "")
    {
        parent::__construct($apiUrl, $username, $password);

        $this->tokensUri = $tokensUri;
        $this->token = $existingToken;
    }

    /**
     * For long duration API conversations the token can be persisted externally and set using this method.
     *
     * @param $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    protected function applyAuthenticationDetailsToRequest(HttpRequest $request)
    {
        if ($this->gettingToken) {
            parent::applyAuthenticationDetailsToRequest($request);
            return;
        }

        if ($this->token == "") {
            $this->getToken();
        }

        $this->applyTokenAuthorizationHeader($request);
    }

    /**
     * A placeholder to be overriden usually to store the token in a session or somewhere similar
     *
     * @param $token
     */
    protected function onTokenReceived($token)
    {

    }

    protected final function getToken()
    {
        $this->gettingToken = true;

        try {
            $response = $this->makeRequest($this->getTokenRequest());
        } catch (RestImplementationException $er) {
            $this->gettingToken = false;
            throw new RestAuthenticationException("The api credentials were rejected.");
        }

        $this->gettingToken = false;
        $this->token = $this->extractTokenFromResponse($response);

        $this->onTokenReceived($this->token);
    }

    /**
     * @param mixed $response
     * @return string
     */
    protected function extractTokenFromResponse($response)
    {
        return $response->token;
    }

    /**
     * @return RestHttpRequest
     */
    protected function getTokenRequest()
    {
        return new RestHttpRequest($this->tokensUri, "post", "");
    }

    /**
     * @param HttpRequest $request
     */
    protected function applyTokenAuthorizationHeader(HttpRequest $request)
    {
        $request->addHeader("Authorization", "Token token=\"" . $this->token . "\"/");
    }
}
