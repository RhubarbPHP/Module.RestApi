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

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Exceptions\CoreException;
use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\Exceptions\RhubarbException;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Response\JsonResponse;
use Rhubarb\Crown\Response\NotAuthorisedResponse;
use Rhubarb\Crown\Response\Response;
use Rhubarb\Crown\UrlHandlers\UrlHandler;
use Rhubarb\RestApi\Authentication\AuthenticationProvider;
use Rhubarb\RestApi\Exceptions\RestImplementationException;

/**
 * A base class to provide some structure to REST format URL handling.
 *
 * This is an abstract class as it only provides the pattern of use. It does not for instance have any logic
 * to determine if this handler is appropriate for a given URL.
 *
 * The workings of this class is simple - combine the HTTP method with the request Accept MIME type and pass
 * control to a function of the same name. For example GET text/html will call a function GetHtml().
 *
 * For this to work you must override getSupportedHttpMethods() and getSupportedMimeTypes() to register your
 * interest in valid http methods and mime types.
 *
 * Note that if a MIME type is supported, you must implement all of the HTTP methods that you are implementing
 * for the other MIME types. If an allegedly supported combination does not have a corresponding function a
 * RestImplementationException will be thrown.
 *
 * @package Rhubarb\Crown\UrlHandlers
 */
abstract class RestHandler extends UrlHandler
{
    /**
     * By default we only support HTML. Override this to allow for json and xml etc.
     *
     * The response should be an array with mime type to abbreviation pairs.
     *
     * @return array
     */
    protected function getSupportedMimeTypes()
    {
        return ["text/html" => "html"];
    }

    /**
     * Returns an array of the HTTP methods this handler supports.
     *
     * @return array
     */
    protected function getSupportedHttpMethods()
    {
        return ["get"];
    }

    /**
     * If you require an authenticated user to handle the request, you can return the name of an authentication provider class
     *
     * Alternatively if a default authentication provider class name has been set this will be used instead.
     *
     * @see RestAuthenticationProvider::SetDefaultAuthenticationProviderClassName()
     * @return null
     */
    protected function createAuthenticationProvider()
    {
        return null;
    }

    protected final function getAuthenticationProvider()
    {
        $provider = $this->createAuthenticationProvider();

        // Allow the handler to return false to indicate the url should be publicly accessible.
        if ($provider === false) {
            return null;
        }

        if ($provider != null) {
            return $provider;
        }

        if (AuthenticationProvider::getDefaultAuthenticationProviderClassName()) {
            $className = AuthenticationProvider::getDefaultAuthenticationProviderClassName();

            return new $className();
        }

        return null;
    }

    protected function authenticate(Request $request)
    {
        $authenticationProvider = $this->getAuthenticationProvider();

        if ($authenticationProvider != null) {
            $response = $authenticationProvider->authenticate($request);

            if ($response instanceof Response) {
                throw new ForceResponseException($response);
            }

            if ($response) {
                Log::debug("Authentication Succeeded", "RESTAPI");
                return true;
            }

            Log::warning("Authentication Failed", "RESTAPI");

            return false;
        }

        return true;
    }

    protected function generateResponseForRequest($request = null, $currentUrlFragment = "")
    {
        if (!($request instanceof WebRequest)){
            throw new RestImplementationException("Rest handlers can only process Web Requests");
        }

        try {
            if (!$this->authenticate($request)) {
                return new NotAuthorisedResponse();
            }
        } catch (ForceResponseException $ex) {
            Log::warning("Authentication Failed: Forcing 401 Response", "RESTAPI");
            return $ex->getResponse();
        }

        $types = $this->getSupportedMimeTypes();
        $methods = $this->getSupportedHttpMethods();

        $typeString = $request->getAcceptsRequestMimeType();

        $type = false;

        $method = strtolower($request->Server("REQUEST_METHOD"));

        if ($method == "") {
            $method = "get";
        }

        foreach ($types as $possibleType => $match) {
            if (stripos($typeString, $possibleType) !== false) {
                $type = $possibleType;
                // First match wins
                break;
            }
        }

        if (!$type) {
            return false;
        }

        if (!isset($types[$type])) {
            Log::warning("Rest url doesn't support " . $type, "RESTAPI");
            return false;
        }

        // If GET is allowed then HEAD must also be allowed.
        if ($method == "head" && !in_array($method, $methods) && in_array("get", $methods)) {
            $methods[] = "head";
        }

        if (!in_array($method, $methods)) {
            Log::warning("Rest url doesn't support " . $method, "RESTAPI");

            $this->handleInvalidMethod($method);
        }

        $correctMethodName = $method . $types[$type];

        if (!method_exists($this, $correctMethodName)) {
            throw new RestImplementationException("The REST end point `" . $correctMethodName . "` could not be found in handler `" . get_class($this) . "`");
        }

        return call_user_func([$this, $correctMethodName], $request);
    }

    /**
     * Override to handle the case where an HTTP method is unsupported.
     *
     * This should throw a ForceResponseException
     *
     * @param $method
     * @throws \Rhubarb\Crown\Exceptions\ForceResponseException
     */
    protected function handleInvalidMethod($method)
    {
        $emptyResponse = new Response();
        $emptyResponse->setHeader("HTTP/1.1 405 Method $method Not Allowed", false);
        $emptyResponse->setHeader("Allow", implode(", ", $this->getSupportedHttpMethods()));

        throw new ForceResponseException($emptyResponse);
    }

    public function generateResponseForException(RhubarbException $er)
    {
        $date = new RhubarbDateTime("now");

        $response = new \stdClass();
        $response->result = new \stdClass();
        $response->result->status = false;
        $response->result->timestamp = $date->format("c");
        $response->result->message = $er->getPrivateMessage();

        $json = new JsonResponse();
        $json->setContent($response);

        return $json;
    }
}