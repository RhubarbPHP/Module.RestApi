<?php

namespace Rhubarb\RestApi\Adapters;

use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\RestApi\Exceptions\ResourceNotFoundException;
use Rhubarb\RestApi\Resources\ListResource;

/**
 * Provides a basic pattern to present common resource types in a consistant way.
 */
abstract class ResourceAdapter
{
    /**
     * @param $payload
     * @param $params
     * @param null|WebRequest $request
     * @return mixed
     * @throws ResourceNotFoundException
     */
    public function put($payload, $params, ?WebRequest $request)
    {
        $resource = $this->get($params, $request);

        $this->putResource($payload);

        return $this->get($params, $request);
    }

    public function get($params, ?WebRequest $request)
    {
        $id = $params["id"];

        $payload = $this->makeResourceByIdentifier($id);

        return $payload;
    }

    public abstract function putResource($resource);

    public abstract function makeResourceByIdentifier($id);

    public abstract function makeResourceFromData($data);

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

    public abstract function post($payload, $params, WebRequest $request);

    public abstract function delete($payload, $params, ?WebRequest $request);

    protected abstract function countItems($rangeStart, $rangeEnd, $params, ?WebRequest $request);

    protected abstract function getItems($rangeStart, $rangeEnd, $params, ?WebRequest $request);
}