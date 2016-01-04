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

require_once __DIR__ . '/RestClient.php';

use Rhubarb\Crown\Http\HttpRequest;

/**
 * Extends RestClient by adding support for HTTP basic authentication.
 */
class BasicAuthenticatedRestClient extends RestClient
{
    protected $username;
    protected $password;

    public function __construct($apiUrl, $username, $password)
    {
        parent::__construct($apiUrl);

        $this->username = $username;
        $this->password = $password;
    }

    protected function applyAuthenticationDetailsToRequest(HttpRequest $request)
    {
        $request->addHeader(
            "Authorization",
            "Basic " .
            base64_encode(base64_encode($this->username) . ":" . base64_encode($this->password))
        );
    }
} 
