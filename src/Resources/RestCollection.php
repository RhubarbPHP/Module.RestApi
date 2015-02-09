<?php

namespace Rhubarb\Crown\RestApi\Resources;

use Rhubarb\Crown\Context;
use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\RestApi\UrlHandlers\RestHandler;

/** 
 * A resource representing a collection of other resources.
 *
 * @package Rhubarb\Crown\RestApi\Resources
 * @author      acuthbert
 * @copyright   2013 GCD Technologies Ltd.
 */
class RestCollection extends RestResource
{
	protected $_restResource = "";

	protected $_maximumCollectionSize = 100;

	public function __construct( $restResource, RestResource $parentResource = null )
	{
		$this->_restResource = $restResource;

        parent::__construct( null, $parentResource );
	}

	/**
	 * Test to see if the given resource identifier exists in the collection of resources.
	 *
	 * @param $resourceIdentifier
	 * @return True if it exists, false if it does not.
	 */
	public function ContainsResourceIdentifier( $resourceIdentifier )
	{
		// This will be very slow however the base implementation can do nothing else.
		// Inheritors of this class should override this if they can do this faster!
		$items = $this->GetItems( 0, 999999999 );

		foreach( $items[0] as $item )
		{
			if ( $item->_id = $resourceIdentifier )
			{
				return true;
			}
		}

		return false;
	}

	protected function GetResourceName()
	{
		return str_replace( "Resource", "", basename( str_replace( "\\", "/", get_class( $this->_restResource ) ) ) );
	}

	public function GetHref( $nonCanonicalUrlTemplate = "" )
	{
		$urlTemplate = RestResource::GetCanonicalResourceUrl( get_class( $this->_restResource ) );

		if ( !$urlTemplate && $nonCanonicalUrlTemplate !== "" )
		{
			$urlTemplate = $nonCanonicalUrlTemplate;
		}

		if ( $urlTemplate )
		{
			$request = Context::CurrentRequest();

			$urlStub = ( ( $request->Server( "SERVER_PORT" ) == 443 ) ? "https://" : "http://" ).
				$request->Server( "HTTP_HOST" );

			return $urlStub.$urlTemplate."/".$this->_id;
		}

		return "";
	}

	public function Get( RestHandler $handler = null )
	{
		$request = Context::CurrentRequest();

		$rangeHeader = $request->Server( "HTTP_RANGE" );

		$rangeStart = 0;
		$rangeEnd = $this->_maximumCollectionSize - 1;

		if ( $rangeHeader )
		{
			$rangeHeader = str_replace( "resources=", "", $rangeHeader );

			$parts = explode( "-", $rangeHeader );

			$fromText = false;
			$toText = false;

			if ( sizeof( $parts ) > 0 )
			{
				$fromText = (int) $parts[0];
			}

			if ( sizeof( $parts ) > 1 )
			{
				$toText = (int) $parts[1];
			}

			if ( $fromText )
			{
				$rangeStart = $fromText;
			}

			if ( $toText )
			{
				$rangeEnd = min( $toText, $rangeStart + ( $this->_maximumCollectionSize - 1 ) );
			}
		}

		$resource = parent::Get();

		$since = null;

		if ( $request->Header( "If-Modified-Since" ) != "" )
		{
			$since = new RhubarbDateTime( $request->Header( "If-Modified-Since" ) );
		}

		list( $resource->items, $resource->count ) = $this->GetItems( $rangeStart, $rangeEnd, $since );

		$resource->range = new \stdClass();
		$resource->range->from = $rangeStart;
		$resource->range->to = min( $rangeEnd, $resource->count - 1 );

		return $resource;
	}

	/**
	 * Implement GetItems to return the items for the collection.
	 *
	 * @param $from
	 * @param $to
	 * @param RhubarbDateTime $since   Optionally a date and time to filter the items for those modified since.
	 * @return array    Return a two item array, the first is the items within the range. The second is the overall
	 *                    number of items available
	 */
	protected function GetItems( $from, $to, RhubarbDateTime $since = null )
	{
		return [ [], 0 ];
	}

	public function ValidateRequestPayload( $payload, $method )
	{
		/**
		 * Collections aren't qualified to answer the question about payload validity
		 * We need to ask the actual resource instead.
		 */
		$this->_restResource->ValidateRequestPayload( $payload, $method );
	}
}