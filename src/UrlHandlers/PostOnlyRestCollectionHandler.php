<?php

namespace Rhubarb\Crown\RestApi\UrlHandlers;

class PostOnlyRestCollectionHandler extends RestCollectionHandler
{
	public function __construct( $restResourceClassName, $childUrlHandlers = [ ] )
	{
		parent::__construct( $restResourceClassName, $childUrlHandlers, [ "post" ] );
	}
}