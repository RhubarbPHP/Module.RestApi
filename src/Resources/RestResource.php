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

namespace Rhubarb\RestApi\Resources;

use Rhubarb\Crown\Context;
use Rhubarb\RestApi\Exceptions\RestImplementationException;
use Rhubarb\RestApi\Exceptions\RestRequestPayloadValidationException;
use Rhubarb\RestApi\UrlHandlers\RestHandler;

/**
 * Represents an API resource.
 *
 */
abstract class RestResource
{
    protected $href;

    private static $resourceUrls = [];

    protected $parentResource = null;

    public function __construct(RestResource $parentResource = null)
    {
        $this->parentResource = $parentResource;
    }

    protected function getResourceName()
    {
        return str_replace("Resource", "", basename(str_replace("\\", "/", get_class($this))));
    }

    public static function registerCanonicalResourceUrl($resourceClassName, $url)
    {
        self::$resourceUrls[ltrim($resourceClassName, "\\")] = $url;
    }

    public static function getCanonicalResourceUrl($resourceClassName)
    {
        if (isset(self::$resourceUrls[$resourceClassName])) {
            return self::$resourceUrls[$resourceClassName];
        }

        return false;
    }

    /**
     * @param mixed $url
     */
    public function setHref($url)
    {
        $this->href = $url;
    }

    public function summary(RestHandler $handler = null)
    {
        return $this->getSkeleton($handler);
    }

    protected function link(RestHandler $handler = null)
    {
        $encapsulatedForm = new \stdClass();
        $encapsulatedForm->rel = $this->getResourceName();

        $href = $this->getRelativeUrl();

        if ($href) {
            $encapsulatedForm->href = $href;
        }

        return $encapsulatedForm;
    }

    protected function getSkeleton(RestHandler $handler = null)
    {
        $encapsulatedForm = new \stdClass();

        $href = $handler->getUrl();

        if ($href) {
            $encapsulatedForm->_href = $href;
        }

        return $encapsulatedForm;
    }

    public function get(RestHandler $handler = null)
    {
        return $this->getSkeleton($handler);
    }

    public function head(RestHandler $handler = null)
    {
        // HEAD requests must behave the same as get
        return $this->get($handler);
    }

    public function delete(RestHandler $handler = null)
    {
        throw new RestImplementationException();
    }

    public function put($restResource, RestHandler $handler = null)
    {
        throw new RestImplementationException();
    }

    public function post($restResource, RestHandler $handler = null)
    {
        throw new RestImplementationException();
    }

    /**
     * Validate that the payload is valid for the request.
     *
     * This is not the only chance to validate the payload. Throwing an exception
     * during the act of handling the request will cause an error response to be
     * given, however it does provide a nice place to do it.
     *
     * If using ModelRestResource you don't need to validate properties which your
     * model validation will handle anyway.
     *
     * Throw a RestPayloadValidationException if the validation should fail.
     *
     * The base implementation simply checks that there is an actual array payload for
     * put and post operations.
     *
     * @param mixed $payload
     * @param string $method
     * @throws RestRequestPayloadValidationException
     */
    public function validateRequestPayload($payload, $method)
    {
        switch ($method) {
            case "post":
            case "put":

                if (!is_array($payload)) {
                    throw new RestRequestPayloadValidationException(
                        "POST and PUT options require a JSON encoded " .
                        "resource object in the body of the request.");
                }

                break;
        }
    }
}