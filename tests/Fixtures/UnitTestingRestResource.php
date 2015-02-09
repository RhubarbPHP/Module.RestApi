<?php

namespace Rhubarb\Crown\RestApi\UnitTesting;

use Rhubarb\Crown\RestApi\Resources\RestCollection;
use Rhubarb\Crown\RestApi\Resources\RestResource;
use Rhubarb\Crown\RestApi\UrlHandlers\RestHandler;

class UnitTestingRestResource extends RestResource
{
	public function Get( RestHandler $handler = null )
	{
		$resource = parent::Get();
		$resource->_id = 1;
		$resource->value = "constructed";

		return $resource;
	}

	public function GetCollection()
	{
		return new UnitTestingRestCollection( $this );
	}
} 