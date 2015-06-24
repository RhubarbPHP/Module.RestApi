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

use Rhubarb\Crown\Http\HttpClient;
use Rhubarb\Crown\Http\HttpRequest;
use Rhubarb\Crown\Logging\Log;

/**
 * The base class for Rest clients.
 *
 * Note it is rare you would use this class directly. Most often you will need a client that
 * supports authentication in some way.
 */
class RestClient
{
    protected $apiUrl;

    public function __construct($apiUrl)
    {
        $this->apiUrl = rtrim($apiUrl, "/");
    }

    protected function applyAuthenticationDetailsToRequest(HttpRequest $request)
    {

    }

    public function makeRequest(RestHttpRequest $request)
    {
        Log::debug( "Making ReST request to ".$request->getMethod().":".$request->getUri(), "RESTCLIENT" );

        $request->setApiUrl($this->apiUrl);
        $request->addHeader("Accept", "application/xml");

        $this->applyAuthenticationDetailsToRequest($request);

        $httpClient = HttpClient::getDefaultHttpClient();
        $response = $httpClient->getResponse($request);

        Log::debug( "ReST response received" );
        Log::bulkData( "ReST response data", "RESTCLIENT", $response->getResponseBody() );

        return json_decode($response->getResponseBody());
    }
} 