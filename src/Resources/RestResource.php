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

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\UrlHandlers\UrlHandler;
use Rhubarb\RestApi\Exceptions\RestImplementationException;
use Rhubarb\RestApi\Exceptions\RestRequestPayloadValidationException;
use Rhubarb\RestApi\UrlHandlers\RestApiRootHandler;

/**
 * Represents an API resource.
 *
 */
abstract class RestResource
{
    /** @var string */
    protected $href;

    /** @var RestResource */
    protected $parentResource = null;

    /** @var UrlHandler */
    protected $urlHandler;

    /**
     * True if this resource is being accessed directly from a URL
     * @var bool
     */
    protected $invokedByUrl = false;

    public function __construct(RestResource $parentResource = null)
    {
        $this->parentResource = $parentResource;
    }

    /**
     * Set to true by a RestResourceHandler that is invoking this resource directly.
     *
     * @param $invokedByUrl
     */
    public function setInvokedByUrl($invokedByUrl)
    {
        $this->invokedByUrl = $invokedByUrl;
    }

    public function setUrlHandler(UrlHandler $handler)
    {
        $this->urlHandler = $handler;
    }

    protected function getResourceName()
    {
        return str_replace("Resource", "", basename(str_replace("\\", "/", get_class($this))));
    }

    /**
     * @param string $url
     */
    public function setHref($url)
    {
        $this->href = $url;
    }

    public function summary()
    {
        return $this->getSkeleton();
    }

    protected function link()
    {
        $encapsulatedForm = new \stdClass();
        $encapsulatedForm->rel = $this->getResourceName();

        $href = $this->getHref();

        if ($href) {
            $encapsulatedForm->href = $href;
        }

        return $encapsulatedForm;
    }

    protected function getHref()
    {
        $handler = $this->urlHandler->getParentHandler();

        $root = false;

        // If we have a canonical URL due to a root registration we should give that
        // in preference to the current URL.
        if ($handler instanceof RestApiRootHandler) {
            $root = $handler->getCanonicalUrlForResource($this);
        }

        if (!$root && $this->invokedByUrl) {
            $root = $this->urlHandler->getUrl();
        }

        return $root;
    }

    /**
     * Called when a resource can't be returned due to an error state.
     * 
     * @param string $message
     * @return \stdClass
     */
    protected function buildErrorResponse($message = "")
    {
        $date = new RhubarbDateTime("now");

        $response = new \stdClass();
        $response->result = new \stdClass();
        $response->result->status = false;
        $response->result->timestamp = $date->format("c");
        $response->result->message = $message;

        return $response;
    }

    protected function getSkeleton()
    {
        $encapsulatedForm = new \stdClass();

        $href = $this->getHref();

        if ($href) {
            $encapsulatedForm->_href = $href;
        }

        return $encapsulatedForm;
    }

    public function get()
    {
        return $this->getSkeleton();
    }

    public function head()
    {
        // HEAD requests must behave the same as get
        return $this->get();
    }

    public function delete()
    {
        throw new RestImplementationException();
    }

    public function put($restResource)
    {
        throw new RestImplementationException();
    }

    public function post($restResource)
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

    /**
     * To support child resource URLs that have a relationship with this parent you must override this method and
     * take responsibility for creating the resource here.
     *
     * @param $childUrlFragment
     * @return RestResource|bool
     * @throws RestImplementationException
     */
    public function getChildResource($childUrlFragment)
    {
        return false;
    }
}