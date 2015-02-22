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

use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Crown\Http\HttpRequest;

/**
 * A version of the HttpRequest that allows just the URI to be set and combined with a ReST stub URL.
 *
 */
class RestHttpRequest extends HttpRequest
{
    private $uri;

    private $apiUrl;

    public function __construct($uri, $method = "get", $payload = null)
    {
        $this->setUri($uri);
        $this->setMethod($method);
        $this->setPayload(json_encode($payload));

        $this->addHeader("Content-Type", "application/json");

        // Note we don't call the parent constructor as it will try and set the $_url property which isn't
        // valid for RestHttpRequests
        if ($method == "post" || $method == "put") {
            $this->addHeader("Content-Length", strlen($this->getPayload()));
        }
    }

    /**
     * @param mixed $apiUrl
     */
    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;
    }

    /**
     * @return mixed
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * @param mixed $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return mixed
     */
    public function getUri()
    {
        return $this->uri;
    }

    public function setUrl($url)
    {
        throw new ImplementationException("A RestHttpRequest does not support setting the Url directly. Set the Uri and ApiUrl properties separately.");
    }

    public function getUrl()
    {
        $url = rtrim($this->apiUrl, '/') . "/" . ltrim($this->uri, '/');

        return $url;
    }
}