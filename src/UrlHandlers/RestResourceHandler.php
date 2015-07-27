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

namespace Rhubarb\RestApi\UrlHandlers;

require_once __DIR__ . '/RestHandler.php';

use Rhubarb\Crown\Context;
use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\Crown\Response\JsonResponse;
use Rhubarb\RestApi\Exceptions\RestImplementationException;

class RestResourceHandler extends RestHandler
{
    protected $apiResourceClassName = "";

    protected $supportedHttpMethods = ["get", "put", "head", "delete"];

    public function __construct($resourceClassName, $childUrlHandlers = [], $supportedHttpMethods = null)
    {
        $this->apiResourceClassName = $resourceClassName;

        if ($supportedHttpMethods != null) {
            $this->supportedHttpMethods = $supportedHttpMethods;
        }

        parent::__construct($childUrlHandlers);
    }

    /**
     * @return array|string
     */
    public function getRestResourceClassName()
    {
        return $this->apiResourceClassName;
    }

    /**
     * Gets the RestResource object
     *
     * @return mixed
     */
    protected function getRestResource()
    {
        $parentResource = $this->getParentResource();

        if ( $parentResource !== null ){
            $childResource = $parentResource->getChildResource( $this->matchingUrl );
            if ( $childResource ){
                $childResource->setUrlHandler($this);
                return $childResource;
            }
        }

        $className = $this->apiResourceClassName;
        $resource = new $className($this->getParentResource());
        $resource->setUrlHandler($this);

        return $resource;
    }

    protected function getSupportedHttpMethods()
    {
        return $this->supportedHttpMethods;
    }

    protected function getSupportedMimeTypes()
    {
        return [
            "text/html" => "json",
            "application/json" => "json"
        ];
    }

    protected function getRequestPayload()
    {
        $request = Context::currentRequest();
        $payload = $request->getPayload();

        if ($payload instanceof \stdClass) {
            $payload = get_object_vars($payload);
        }

        Log::bulkData("Payload received", "RESTAPI", $payload);

        return $payload;
    }

    protected function handleInvalidMethod($method)
    {
        $response = new JsonResponse($this);
        $response->setContent($this->buildErrorResponse("This API resource does not support the `$method` HTTP method. Supported methods: " . implode(", ",
                $this->getSupportedHttpMethods())));
        $response->setHeader("HTTP/1.1 405 Method $method Not Allowed", false);
        $response->setHeader("Allow", implode(", ", $this->getSupportedHttpMethods()));

        throw new ForceResponseException($response);
    }

    protected function getJson()
    {
        Log::debug("GET " . Context::currentRequest()->UrlPath, "RESTAPI");

        $response = new JsonResponse($this);

        try {
            $resource = $this->getRestResource();
            $resource->setInvokedByUrl(true);
            Log::performance("Got resource", "RESTAPI");
            $resourceOutput = $resource->get();
            Log::performance("Got response", "RESTAPI");
            $response->setContent($resourceOutput);
        } catch (RestImplementationException $er) {
            $response->setContent($this->buildErrorResponse($er->getPublicMessage()));
        }

        Log::bulkData("Api response", "RESTAPI", $response->getContent());

        return $response;
    }

    protected function headJson()
    {
        Log::debug("HEAD " . Context::currentRequest()->UrlPath, "RESTAPI");

        // HEAD requests must be identical in their consequences to a GET so we have to incur
        // the overhead of actually doing a GET transaction.
        $this->getJson();

        // HEAD requests can't return a body
        return "";
    }

    protected function putJson()
    {
        Log::debug("PUT " . Context::currentRequest()->UrlPath, "RESTAPI");

        $response = new JsonResponse($this);

        try {
            $resource = $this->getRestResource();
            $payload = $this->getRequestPayload();
            $resource->validateRequestPayload($payload, "put");

            if ($resource->put($payload, $this)) {
                $response->setContent($this->buildSuccessResponse("The PUT operation completed successfully"));
            } else {
                $response->setContent($this->buildErrorResponse("An unknown error occurred during the operation."));
            }
        } catch (RestImplementationException $er) {
            $response->setContent($this->buildErrorResponse($er->getMessage()));
        }

        Log::bulkData("Api response", "RESTAPI", $response->getContent());

        return $response;
    }

    protected function postJson()
    {
        Log::debug("POST " . Context::currentRequest()->UrlPath, "RESTAPI");

        $jsonResponse = new JsonResponse($this);

        try {
            $resource = $this->getRestResource();
            $payload = $this->getRequestPayload();

            $resource->validateRequestPayload($payload, "post");
            $newItem = $resource->post($payload, $this);

            if ( $newItem || is_array($newItem) ) {
                $jsonResponse->setContent($newItem);
                $jsonResponse->setHeader("HTTP/1.1 201 Created", false);

                if (isset($newItem->_href)) {
                    $jsonResponse->setHeader("Location", $newItem->_href);
                }
            } else {
                $jsonResponse->setContent($this->buildErrorResponse("An unknown error occurred during the operation."));
            }
        } catch (RestImplementationException $er) {
            $jsonResponse->setContent($this->buildErrorResponse($er->getMessage()));
        }

        Log::bulkData("Api response", "RESTAPI", $jsonResponse->getContent());

        return $jsonResponse;
    }

    protected function deleteJson()
    {
        Log::debug("DELETE " . Context::currentRequest()->UrlPath, "RESTAPI");

        $jsonResponse = new JsonResponse($this);

        $resource = $this->getRestResource();

        if ($resource->delete($this)) {
            try {
                $response = $this->buildSuccessResponse("The DELETE operation completed successfully");

                $jsonResponse->setContent($response);
                return $jsonResponse;
            } catch (\Exception $er) {
            }
        }

        $response = $this->buildErrorResponse("The resource could not be deleted.");
        $jsonResponse->setContent($response);

        Log::bulkData("Api response", "RESTAPI", $jsonResponse->getContent());

        return $jsonResponse;
    }

    protected function buildSuccessResponse($message = "")
    {
        $date = new RhubarbDateTime("now");

        $response = new \stdClass();
        $response->result = new \stdClass();
        $response->result->status = true;
        $response->result->timestamp = $date->format("c");
        $response->result->message = $message;

        return $response;
    }

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

    /**
     * get's the resource for the parent handler.
     *
     * Sometimes a resource needs the context of it's parent to check permissions or apply
     * filters.
     *
     * @return bool|mixed
     */
    public function getParentResource()
    {
        $parentHandler = $this->getParentHandler();

        if ($parentHandler instanceof RestResourceHandler) {
            return $parentHandler->getRestResource();
        }

        return null;
    }
}