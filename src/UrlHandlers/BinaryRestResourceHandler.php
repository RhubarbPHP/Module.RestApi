<?php

namespace Rhubarb\RestApi\UrlHandlers;

use Rhubarb\Crown\Logging\Log;
use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Response\BinaryResponse;
use Rhubarb\Crown\Response\JsonResponse;
use Rhubarb\Crown\Response\Response;
use Rhubarb\RestApi\Exceptions\RestImplementationException;
use Rhubarb\RestApi\Exceptions\RestResourceNotFoundException;
use Rhubarb\RestApi\Resources\BinaryRestResource;

class BinaryRestResourceHandler extends RestResourceHandler
{
    public function __construct($resourceClassName, $childUrlHandlers = [], $supportedHttpMethods = null)
    {
        if ($supportedHttpMethods == null) {
            $supportedHttpMethods = ["put", "get"];
        }

        parent::__construct($resourceClassName, $childUrlHandlers, $supportedHttpMethods);
    }

    protected function getSupportedMimeTypes()
    {
        $binaryResponse = new BinaryResponse($this, '', '');
        return [
            'image/jpg' => $binaryResponse,
            'image/jpeg' => $binaryResponse,
            'image/png' => $binaryResponse,
            'image/gif' => $binaryResponse,
            'application/octet-stream' => $binaryResponse,
            'application/json' => new JsonResponse($this),
        ];
    }

    protected function handleGet(WebRequest $request, Response $response)
    {
        Log::debug("GET " . Request::current()->urlPath, "RESTAPI");

        try {
            $resource = $this->getRestResource();
            $resource->setInvokedByUrl(true);
            Log::performance("Got resource", "RESTAPI");
            $resourceOutput = $resource->get();
            Log::performance("Got response", "RESTAPI");

            $fileName = '';
            if ($resource instanceof BinaryRestResource) {
                $fileName = $resource->getFileName();
                $contentType = $resource->getContentType();
            } else {
                $contentType = $request->getAcceptsRequestMimeType();
            }

            $response = new BinaryResponse($this, $resourceOutput, $contentType, $fileName);
        } catch (RestResourceNotFoundException $er) {
            $response = new JsonResponse($this);
            $response->setResponseCode(HttpHeaders::HTTP_STATUS_CLIENT_ERROR_NOT_FOUND);
            $response->setResponseMessage("Resource not found");
            $response->setContent($this->buildErrorResponse("The resource could not be found."));
        } catch (RestImplementationException $er) {
            $response = new JsonResponse($this);
            $response->setResponseCode(HttpHeaders::HTTP_STATUS_SERVER_ERROR_GENERIC);
            $response->setContent($this->buildErrorResponse($er->getPublicMessage()));
        }

        Log::bulkData("Api response", "RESTAPI", $response->getContent());

        return $response;
    }
}
