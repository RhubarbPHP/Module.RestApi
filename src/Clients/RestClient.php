<?php

/*
 * Copyright 2015 RhubarbPHP
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

use Rhubarb\Crown\Exceptions\HttpResponseException;
use Rhubarb\Crown\Http\HttpClient;
use Rhubarb\Crown\Http\HttpRequest;
use Rhubarb\Crown\Http\HttpResponse;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\RestApi\Exceptions\RestAuthenticationException;
use Rhubarb\RestApi\Exceptions\RestImplementationException;

/**
 * The base class for Rest clients.
 *
 * Note it is rare you would use this class directly. Most often you will need a client that
 * supports authentication in some way.
 */
class RestClient
{
    protected $apiUrl;
    public $requireSuccessfulResponse = false;

    public function __construct($apiUrl)
    {
        $this->apiUrl = rtrim($apiUrl, "/");
    }

    protected function applyAuthenticationDetailsToRequest(HttpRequest $request)
    {

    }

    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    public function makeRequest(RestHttpRequest $request)
    {
        Log::debug("Making ReST request to " . $request->getMethod() . ":" . $request->getUri(), "RESTCLIENT");

        $request->setApiUrl($this->getApiUrl());
        // ToDo: refactor this into a JSONRestClient as this is all json specific
        $request->addHeader("Accept", "application/json");

        $this->applyAuthenticationDetailsToRequest($request);

        $httpClient = HttpClient::getDefaultHttpClient();
        $response = $httpClient->getResponse($request);

        Log::debug("ReST response received");
        Log::bulkData("ReST response data", "RESTCLIENT", $response->getResponseBody());

        $this->checkResponse($response);

        $responseObject = $this->parseResponseBody($response->getResponseBody());

        $this->checkResponseBody($responseObject, $response);
        
        return $responseObject;
    }

    /**
     * Parses a response for use as an object
     * @param String $responseBody
     * @return mixed
     */
    protected function parseResponseBody($responseBody)
    {
        return json_decode($responseBody);
    }

    /**
     * @param HttpResponse $response
     * @throws RestAuthenticationException
     */
    protected function checkResponse(HttpResponse $response)
    {
        if ($response->getResponseCode() == 401) {
            throw new RestAuthenticationException();
        }
    }

    /**
     * @param $responseObject
     * @param HttpResponse $response
     * @throws HttpResponseException
     * @throws RestImplementationException
     */
    protected function checkResponseBody($responseObject, HttpResponse $response)
    {
        if ($responseObject === null) {
            Log::error("REST Request was returned with an invalid response", "RESTCLIENT",
                $response->getResponseBody());
            throw new RestImplementationException("A REST Request was returned with an invalid response");
        }

        if ($this->requireSuccessfulResponse && !$response->isSuccess()) {
            throw new HttpResponseException("A REST Request was returned with an error.", null, $response);
        }
    }
}
