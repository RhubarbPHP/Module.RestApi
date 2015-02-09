<?php

namespace Rhubarb\Crown\RestApi\Resources;

use Rhubarb\Crown\Context;
use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\Crown\RestApi\Exceptions\RestImplementationException;
use Rhubarb\Crown\RestApi\Exceptions\RestRequestPayloadValidationException;
use Rhubarb\Crown\RestApi\UrlHandlers\RestHandler;

/** 
 * Represents an API resource
 */
abstract class RestResource
{
	protected $_id;

	protected $_href;

    private static $_resourceUrls = [];

    protected $_parentResource = null;

	public function __construct( $resourceIdentifier = null, RestResource $parentResource = null )
	{
		$this->SetID( $resourceIdentifier );

        $this->_parentResource = $parentResource;
	}

	protected function GetResourceName()
	{
		return str_replace( "Resource", "", basename( str_replace( "\\", "/", get_class( $this ) ) ) );
	}

    public static function RegisterCanonicalResourceUrl( $resourceClassName, $url )
    {
        self::$_resourceUrls[ ltrim( $resourceClassName, "\\" ) ] = $url;
    }

    public static function GetCanonicalResourceUrl( $resourceClassName )
    {
        if ( isset( self::$_resourceUrls[ $resourceClassName ] ) )
        {
            return self::$_resourceUrls[ $resourceClassName ];
        }

        return false;
    }

	protected function SetID( $id )
	{
		$this->_id = $id;
	}

	public function GetCollection()
	{
		return new RestCollection( $this, $this->_parentResource );
	}

	/**
	 * @param mixed $url
	 */
	public function SetHref( $url )
	{
		$this->_href = $url;
	}

	/**
	 * @param string $nonCanonicalUrlTemplate If this resource has no canonical url template then you can supply one instead.
	 * @return mixed
	 */
	public function GetHref( $nonCanonicalUrlTemplate = "" )
	{
		$urlTemplate = RestResource::GetCanonicalResourceUrl( get_class( $this ) );

		if ( !$urlTemplate && $nonCanonicalUrlTemplate !== "" )
		{
			$urlTemplate = $nonCanonicalUrlTemplate;
		}

		if ( $urlTemplate )
		{
			$request = Context::CurrentRequest();

			$urlStub = ( ( $request->Server( "SERVER_PORT" ) == 443 ) ? "https://" : "http://" ).
				$request->Server( "HTTP_HOST" );

			if ( $this->_id && $urlTemplate[ strlen( $urlTemplate ) - 1 ] != "/" )
			{
				return $urlStub.$urlTemplate."/".$this->_id;
			}
		}

		return "";
	}

	public function Summary( RestHandler $handler = null )
	{
		return $this->GetSkeleton( $handler );
	}

	protected function Link( RestHandler $handler = null )
	{
		$encapsulatedForm = new \stdClass();
		$encapsulatedForm->rel = $this->GetResourceName();

		$href = $this->GetHref();

		if ( $href )
		{
			$encapsulatedForm->href = $href;
		}

		return $encapsulatedForm;
	}

	protected function GetSkeleton( RestHandler $handler = null )
	{
		$encapsulatedForm = new \stdClass();

		if ( $this->_id )
		{
			$encapsulatedForm->_id = $this->_id;
		}

		$href = $this->GetHref();

		if ( $href )
		{
			$encapsulatedForm->_href = $href;
		}

		return $encapsulatedForm;
	}

	public function Get( RestHandler $handler = null )
	{
		return $this->GetSkeleton( $handler );
	}

	public function Head( RestHandler $handler = null )
	{
		// HEAD requests must behave the same as Get
		return $this->Get( $handler );
	}

	public function Delete( RestHandler $handler = null )
	{
		throw new RestImplementationException();
	}

	public function Put( $restResource, RestHandler $handler = null )
	{
		throw new RestImplementationException();
	}

	public function Post( $restResource, RestHandler $handler = null )
	{
		throw new RestImplementationException();
	}

	/**
	 * Validate that the payload is valid for the request.
	 *
	 * This is not the only chance to validate the payload. Throwing an exception
	 * during the act of handling the request will cause an error response to be
	 * given, however it does provide a nice place to do it.
	 *
	 * If using ModelRestResource you don't need to validate properties which your
	 * model validation will handle anyway.
	 *
	 * Throw a RestPayloadValidationException if the validation should fail.
	 *
	 * The base implementation simply checks that there is an actual array payload for
	 * put and post operations.
	 *
	 * @param mixed $payload
	 * @param string $method
	 * @throws RestRequestPayloadValidationException
	 */
	public function ValidateRequestPayload( $payload, $method )
	{
		switch( $method )
		{
			case "post":
			case "put":

				if ( !is_array( $payload ) )
				{
					throw new RestRequestPayloadValidationException(
						"POST and PUT options require a JSON encoded ".
						"resource object in the body of the request." );
				}

				break;
		}
	}
}