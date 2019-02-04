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
    public final function get($params, ?WebRequest $request)
    {
        $id = $params["id"];

        $entity = $this->getEntityById($id);

        return $this->transposeEntityToPayload($entity);
    }

    public final function put($payload, $params, ?WebRequest $request)
    {
        $entity = $this->transposePayloadToEntity($payload, $params);

        $entity = $this->get($params, $request);

        $this->performPut($entity, $payload);

        return $this->get($params, $request);
    }

    public final function post($payload, $params, WebRequest $request)
    {
        $entity = $this->transposePayloadToEntity($payload, $params);

        $entity = $this->performPost($payload);

        return $this->transposeEntityToPayload($entity);
    }

    public final function delete($payload, $params, ?WebRequest $request)
    {
        $entity = $this->transposePayloadToEntity($payload, $params);

        return $this->deleteResource($entity);
    }

    public final function list($params, ?WebRequest $request = null)
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

    protected function performPut($entity, $payload)
    {
        throw new RestImplementationException("Missing implementation of " . __FUNCTION__);
    }

    protected function performPost($entity)
    {
        throw new RestImplementationException("Missing implementation of " . __FUNCTION__);
    }

    protected function performDelete($entity)
    {
        throw new RestImplementationException("Missing implementation of " . __FUNCTION__);
    }


    protected function countItems($rangeStart, $rangeEnd, $params, ?WebRequest $request)
    {
        throw new RestImplementationException("Missing implementation of " . __FUNCTION__);
    }

    protected function getItems($rangeStart, $rangeEnd, $params, ?WebRequest $request)
    {
        throw new RestImplementationException("Missing implementation of " . __FUNCTION__);
    }

    protected final function validateRequestPayload($payload)
    {
        if (!is_array($payload)) {
            throw new RequestPayloadValidationException("POST and PUT options require a JSON encoded resource object in the body of the request.");
        }

        return $payload;
    }

    public function getEntityById($id)
    {
        throw new RestImplementationException("Missing implementation of " . __FUNCTION__);
    }

    /**
     * To support converting the received payload into an entity that can be used for future actions
     * @param $payload
     */
    protected abstract function transposePayloadToEntity($payload, $params);

    /**
     * To support converting the received entity into a payload that can be returned for the request
     * @param $payload
     */
    protected abstract function transposeEntityToPayload($payload);
}