<?php

namespace Rhubarb\Crown\RestApi\UrlHandlers;

use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Crown\Response\JsonResponse;
use Rhubarb\Crown\RestApi\Exceptions\RestImplementationException;
use Rhubarb\Crown\RestApi\Resources\RestResource;
use Rhubarb\Crown\UrlHandlers\CollectionUrlHandling;

/** 
 * A RestHandler that knows about urls that can point to either a resource or a collection.
 *
 * This handler will try to instantiate an API resource and then call the appropriate action on it.
 * It supports passing through get, post, put, head and delete methods to the resource type and currently
 * works only with JSON responses.
 *
 * @package Rhubarb\Crown\UrlHandlers
 * @author      acuthbert
 * @copyright   2013 GCD Technologies Ltd.
 */
class RestCollectionHandler extends RestResourceHandler
{
	use CollectionUrlHandling;

	public function __construct( $restResourceClassName, $childUrlHandlers = [ ], $supportedHttpMethods = null )
	{
		$this->_supportedHttpMethods = [ "get", "post", "put", "head", "delete" ];

		parent::__construct( $restResourceClassName, $childUrlHandlers, $supportedHttpMethods );
	}

	protected function GetResource()
	{
		// We will either be returning a resource or a collection.
		// However even if returning a resource, we first need to instantiate the collection
		// to verify the resource is one of the items in the collection, in case it has been
		// filtered for security reasons.
		$class = $this->_apiResourceClassName;
		$resource = new $class( null, $this->GetParentResource() );

		$collection = $resource->GetCollection();

		if ( $this->IsCollection() )
		{
			return $collection;
		}
		else
		{
			if ( !$this->_resourceIdentifier )
			{
				throw new RestImplementationException( "The resource identifier for was invalid." );
			}

			if ( !$collection->ContainsResourceIdentifier( $this->_resourceIdentifier ) )
			{
				throw new RestImplementationException( "That resource identifier does not exist in the collection." );
			}

			$className = $this->_apiResourceClassName;
			$resource = new $className( $this->_resourceIdentifier, $this->GetParentResource() );

			return $resource;
		}
	}
}