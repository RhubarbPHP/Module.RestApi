<?php

namespace Rhubarb\RestApi\Adapters;

use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\RestApi\Exceptions\ResourceNotFoundException;
use Rhubarb\RestApi\Exceptions\RequestPayloadValidationException;
use Rhubarb\RestApi\Resources\ListResource;

/**
 * Provides a basic pattern to present common resource types in a consistant way.
 */
abstract class ResourceAdapter
{
    public function get($params, ?WebRequest $request)
    {
        $id = $params["id"];

        $payload = $this->makeResourceByIdentifier($id);

        return $payload;
    }

    /**
     * @param $payload
     * @param $params
     * @param null|WebRequest $request
     * @return mixed
     * @throws ResourceNotFoundException
     */
    public function put($payload, $params, ?WebRequest $request)
    {
        $payload = $this->validatePutRequestPayload($payload);

        $payload = $this->applyParamsToPayload($payload, $params);

        $resource = $this->get($params, $request);

        $this->putResource($payload);

        return $this->get($params, $request);
    }

    protected function validatePutRequestPayload($payload)
    {
        $this->validateRequestPayload($payload);

        return $payload;
    }

    public function putResource($resource)
    {
        throw new RestImplementationException("Missing implementation of " . __FUNCTION__);
    }

    public function post($payload, $params, WebRequest $request)
    {
        $payload = $this->validatePostRequestPayload($payload);

        return $this->postResource($payload);
    }

    protected function validatePostRequestPayload($payload)
    {
        return $this->validateRequestPayload($payload);
    }

    public function postResource($resource)
    {
        throw new RestImplementationException("Missing implementation of " . __FUNCTION__);
    }

    public function delete($payload, $params, ?WebRequest $request)
    {
        throw new RestImplementationException("Missing implementation of " . __FUNCTION__);
    }


    protected function applyParamsToPayload($payload, $params)
    {
        return $payload;
    }

    private final function validateRequestPayload($payload)
    {
        if (!is_array($payload)) {
            throw new RequestPayloadValidationException("POST and PUT options require a JSON encoded resource object in the body of the request.");
        }

        return $payload;
    }


    public function makeResourceByIdentifier($id)
    {
        throw new RestImplementationException("Missing implementation of " . __FUNCTION__);
    }

    public function makeResourceFromData($data)
    {
        throw new RestImplementationException("Missing implementation of " . __FUNCTION__);
    }

    public function list($params, ?WebRequest $request = null)
    {
        $start = $request->get("start", 0);
        $end = $request->get("end", 99);

        $response = new ListResource();
        $response->range->start = $start;
        $response->range->end = $end;

        $response->count = $this->countItems($start, $end, $params, $request);
        $response->items = $this->getItems($start, $end, $params, $request);

        return $response;
    }

    protected function countItems($rangeStart, $rangeEnd, $params, ?WebRequest $request)
    {
        throw new RestImplementationException("Missing implementation of " . __FUNCTION__);
    }

    protected function getItems($rangeStart, $rangeEnd, $params, ?WebRequest $request)
    {
        throw new RestImplementationException("Missing implementation of " . __FUNCTION__);
    }
}