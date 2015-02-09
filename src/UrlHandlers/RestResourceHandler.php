<?php

namespace Rhubarb\Crown\RestApi\UrlHandlers;

use Rhubarb\Crown\Context;
use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\Crown\Response\JsonResponse;
use Rhubarb\Crown\RestApi\Exceptions\RestImplementationException;

/**
 *
 *
 * @package Rhubarb\Crown\RestApi\UrlHandlers
 * @author      acuthbert
 * @copyright   2013 GCD Technologies Ltd.
 */
class RestResourceHandler extends RestHandler
{
	protected $_apiResourceClassName = "";

	protected $_supportedHttpMethods = [ "get", "put", "head", "delete" ];

	public function __construct( $restResourceClassName, $childUrlHandlers = [ ], $supportedHttpMethods = null )
	{
		$this->_apiResourceClassName = $restResourceClassName;

		if ( $supportedHttpMethods != null )
		{
			$this->_supportedHttpMethods = $supportedHttpMethods;
		}

		parent::__construct( $childUrlHandlers );
	}

    /**
     * @return array|string
     */
    public function GetRestResourceClassName()
    {
        return $this->_apiResourceClassName;
    }

	/**
	 * Get's the resource targeted by the URL
	 *
	 * @return mixed
	 */
	protected function GetResource()
	{
		$className = $this->_apiResourceClassName;
		$resource = new $className( null, $this->GetParentResource() );

		return $resource;
	}

	protected function GetSupportedHttpMethods()
	{
		return $this->_supportedHttpMethods;
	}

	protected function GetSupportedMimeTypes()
	{
		return array(
			"text/html" => "json",
			"application/json" => "json"
		);
	}

	protected function GetRequestPayload()
	{
		$request = Context::CurrentRequest();
		$payload = $request->GetPayload();

		if ( $payload instanceof \stdClass )
		{
			$payload = get_object_vars( $payload );
		}

		Log::BulkData( "Payload received", "RESTAPI", $payload );

		return $payload;
	}

	protected function HandleInvalidMethod( $method )
	{
		$response = new JsonResponse( $this );
		$response->SetContent( $this->BuildErrorResponse( "This API resource does not support the `$method` HTTP method. Supported methods: ".implode( ", ", $this->GetSupportedHttpMethods() ) ) );
		$response->SetHeader( "HTTP/1.1 405 Method $method Not Allowed", false );
		$response->SetHeader( "Allow", implode( ", ", $this->GetSupportedHttpMethods() ) );

		throw new ForceResponseException( $response );
	}

	protected function GetJson()
	{
		Log::Debug( "GET ".Context::CurrentRequest()->UrlPath, "RESTAPI" );

		$response = new JsonResponse( $this );

		try
		{
			$resource = $this->GetResource();
			$resourceOutput = $resource->Get( $this );
			$response->SetContent( $resourceOutput );
		}
		catch( RestImplementationException $er )
		{
			$response->SetContent( $this->BuildErrorResponse( $er->getMessage() ) );
		}

		Log::BulkData( "Api response", "RESTAPI", $response->GetContent() );

		return $response;
	}

	protected function HeadJson()
	{
		Log::Debug( "HEAD ".Context::CurrentRequest()->UrlPath, "RESTAPI" );

		// HEAD requests must be identical in their consequences to a GET so we have to incur
		// the overhead of actually doing a GET transaction.
		$this->GetJson();

		// HEAD requests can't return a body
		return "";
	}

	protected function PutJson()
	{
		Log::Debug( "PUT ".Context::CurrentRequest()->UrlPath, "RESTAPI" );

		$response = new JsonResponse( $this );

		try
		{
			$resource = $this->GetResource();
			$payload = $this->GetRequestPayload();
			$resource->ValidateRequestPayload( $payload, "put" );

			if ( $resource->Put( $payload, $this ) )
			{
				$response->SetContent( $this->BuildSuccessResponse( "The PUT operation completed successfully" ) );
			}
			else
			{
				$response->SetContent( $this->BuildErrorResponse( "An unknown error occurred during the operation." ) );
			}
		}
		catch( RestImplementationException $er )
		{
			$response->SetContent( $this->BuildErrorResponse( $er->getMessage() ) );
		}

		Log::BulkData( "Api response", "RESTAPI", $response->GetContent() );

		return $response;
	}

	protected function PostJson()
	{
		Log::Debug( "POST ".Context::CurrentRequest()->UrlPath, "RESTAPI" );

		$jsonResponse = new JsonResponse( $this );

		try
		{
			$resource = $this->GetResource();
			$payload = $this->GetRequestPayload();

			$resource->ValidateRequestPayload( $payload, "post" );

			if ( $newItem = $resource->Post( $payload, $this ) )
			{
				$jsonResponse->SetContent( $newItem );
				$jsonResponse->SetHeader( "HTTP/1.1 201 Created", false );

				if ( isset( $newItem->_href ) )
				{
					$jsonResponse->SetHeader( "Location", $newItem->_href );
				}
			}
			else
			{
				$jsonResponse->SetContent( $this->BuildErrorResponse( "An unknown error occurred during the operation." ) );
			}
		}
		catch( RestImplementationException $er )
		{
			$jsonResponse->SetContent( $this->BuildErrorResponse( $er->getMessage() ) );
		}

		Log::BulkData( "Api response", "RESTAPI", $jsonResponse->GetContent() );

		return $jsonResponse;
	}

	protected function DeleteJson()
	{
		Log::Debug( "DELETE ".Context::CurrentRequest()->UrlPath, "RESTAPI" );

		$jsonResponse = new JsonResponse( $this );

		$resource = $this->GetResource();

		if ( $resource->Delete( $this ) )
		{
			try
			{
				$response = $this->BuildSuccessResponse( "The DELETE operation completed successfully" );

				$jsonResponse->SetContent( $response );
				return $jsonResponse;
			}
			catch( \Exception $er )
			{}
		}

		$response = $this->BuildErrorResponse( "The resource could not be deleted." );
		$jsonResponse->SetContent( $response );

		Log::BulkData( "Api response", "RESTAPI", $jsonResponse->GetContent() );

		return $jsonResponse;
	}

	protected function BuildSuccessResponse( $message = "" )
	{
		$date = new RhubarbDateTime( "now" );

		$response = new \stdClass();
		$response->result = new \stdClass();
		$response->result->status = true;
		$response->result->timestamp = $date->format( "c" );
		$response->result->message = $message;

		return $response;
	}

	protected function BuildErrorResponse( $message = "" )
	{
		$date = new RhubarbDateTime( "now" );

		$response = new \stdClass();
		$response->result = new \stdClass();
		$response->result->status = false;
		$response->result->timestamp = $date->format( "c" );
		$response->result->message = $message;

		return $response;
	}

	/**
	 * Get's the resource for the parent handler.
	 *
	 * Sometimes a resource needs the context of it's parent to check permissions or apply
	 * filters.
	 *
	 * @return bool|mixed
	 */
	public function GetParentResource()
	{
		$parentHandler = $this->GetParentHandler();

		if ( $parentHandler instanceof RestResourceHandler )
		{
			return $parentHandler->GetResource();
		}

		return null;
	}
}