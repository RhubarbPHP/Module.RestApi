<?php

namespace Rhubarb\RestApi\UrlHandlers;

use Rhubarb\Crown\Context;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Response\BinaryResponse;
use Rhubarb\Crown\Response\JsonResponse;
use Rhubarb\RestApi\Exceptions\RestImplementationException;
use Rhubarb\RestApi\Exceptions\RestResourceNotFoundException;

class BinaryRestResourceHandler extends RestResourceHandler
{
    public function __construct($resourceClassName, $childUrlHandlers = [], $supportedHttpMethods = null)
    {
        if ($supportedHttpMethods == null){
            $supportedHttpMethods = ["put", "get"];
        }

        parent::__construct($resourceClassName, $childUrlHandlers, $supportedHttpMethods);
    }

    protected function getSupportedMimeTypes()
    {
        return [
            "application/json" => "json",
            "image/jpg" => "binary",
            "image/jpeg" => "binary",
            "image/png" => "binary",
            "image/gif" => "binary",
            "application/octet-stream" => "binary"
        ];
    }

    protected function getBinary(Request $request)
    {
        Log::debug("GET " . Context::currentRequest()->UrlPath, "RESTAPI");

        try {
            $resource = $this->getRestResource();
            $resource->setInvokedByUrl(true);
            Log::performance("Got resource", "RESTAPI");
            $resourceOutput = $resource->get();
            Log::performance("Got response", "RESTAPI");
            $response = new BinaryResponse($this, $resourceOutput, $request->getAcceptsRequestMimeType() );
        } catch (RestResourceNotFoundException $er) {
            $response = new JsonResponse($this);
            $response->setResponseCode(404);
            $response->setResponseMessage("Resource not found");
            $response->setContent($this->buildErrorResponse("The resource could not be found."));
        } catch (RestImplementationException $er) {
            $response = new JsonResponse($this);
            $response->setContent($this->buildErrorResponse($er->getPublicMessage()));
        }

        Log::bulkData("Api response", "RESTAPI", $response->getContent());

        return $response;
    }
}