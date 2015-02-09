<?php

namespace Rhubarb\Crown\RestApi\UnitTesting;

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\RestApi\Resources\RestCollection;
use Rhubarb\Crown\RestApi\UrlHandlers\RestHandler;

class UnitTestingRestCollection extends RestCollection
{
	protected function GetItems($from, $to, RhubarbDateTime $since = null)
	{
		$item = new \stdClass();
		$item->_id = 1;

		return [ [ $item ], 1 ];
	}


	public function Get( RestHandler $handler = null )
	{
		return "collection";
	}
}