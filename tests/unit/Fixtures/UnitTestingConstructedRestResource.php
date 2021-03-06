<?php

namespace Rhubarb\RestApi\Tests\Fixtures;

use Rhubarb\RestApi\Resources\ItemRestResource;
use Rhubarb\RestApi\Resources\RestResource;
use Rhubarb\RestApi\UrlHandlers\RestHandler;

class UnitTestingConstructedRestResource extends ItemRestResource
{
    private $resourceBody;

    public function __construct($resourceBody, RestResource $parentResource = null)
    {
        $this->resourceBody = $resourceBody;

        parent::__construct($resourceBody->_id, $parentResource);
    }

    public function get(RestHandler $handler = null)
    {
        return $this->resourceBody;
    }
}