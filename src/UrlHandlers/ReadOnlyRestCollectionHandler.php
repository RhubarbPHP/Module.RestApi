<?php

namespace Rhubarb\Crown\RestApi\UrlHandlers;

class ReadOnlyRestCollectionHandler extends RestCollectionHandler
{
	public function __construct($restResourceClassName, $childUrlHandlers = [] )
	{
		parent::__construct( $restResourceClassName, $childUrlHandlers, [ "get" ] );
	}
} 