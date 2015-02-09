<?php

namespace Rhubarb\Crown\RestApi\UrlHandlers;

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Exceptions\CoreException;
use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Response\HtmlResponse;
use Rhubarb\Crown\Response\JsonResponse;
use Rhubarb\Crown\Response\NotAuthorisedResponse;
use Rhubarb\Crown\Response\Response;
use Rhubarb\Crown\RestApi\Authentication\AuthenticationProvider;
use Rhubarb\Crown\RestApi\Exceptions\RestImplementationException;
use Rhubarb\Crown\UrlHandlers\UrlHandler;

/**
 * A base class to provide some structure to REST format URL handling.
 *
 * This is an abstract class as it only provides the pattern of use. It does not for instance have any logic
 * to determine if this handler is appropriate for a given URL.
 *
 * The workings of this class is simple - combine the HTTP method with the request Accept MIME type and pass
 * control to a function of the same name. For example GET text/html will call a function GetHtml().
 *
 * For this to work you must override GetSupportedHttpMethods() and GetSupportedMimeTypes() to register your
 * interest in valid http methods and mime types.
 *
 * Note that if a MIME type is supported, you must implement all of the HTTP methods that you are implementing
 * for the other MIME types. If an allegedly supported combination does not have a corresponding function a
 * RestImplementationException will be thrown.
 *
 * @package Rhubarb\Crown\UrlHandlers
 */
abstract class RestHandler extends UrlHandler
{
	/**
	 * By default we only support HTML. Override this to allow for json and xml etc.
	 *
	 * The response should be an array with mime type to abbreviation pairs.
	 *
	 * @return array
	 */
	protected function GetSupportedMimeTypes()
	{
		return array( "text/html" => "html" );
	}

	/**
	 * Returns an array of the HTTP methods this handler supports.
	 *
	 * @return array
	 */
	protected function GetSupportedHttpMethods()
	{
		return array( "get" );
	}

	/**
	 * If you require an authenticated user to handle the request, you can return the name of an authentication provider class
	 *
	 * Alternatively if a default authentication provider class name has been set this will be used instead.
	 *
	 * @see RestAuthenticationProvider::SetDefaultAuthenticationProviderClassName()
	 * @return null
	 */
	protected function CreateAuthenticationProvider()
	{
		return null;
	}

	protected final function GetAuthenticationProvider()
	{
		$provider = $this->CreateAuthenticationProvider();

		// Allow the handler to return false to indicate the url should be publicly accessible.
		if ( $provider === false )
		{
			return null;
		}

		if ( $provider != null )
		{
			return $provider;
		}

		if ( AuthenticationProvider::getDefaultAuthenticationProviderClassName() )
		{
			$className = AuthenticationProvider::getDefaultAuthenticationProviderClassName();

			return new $className();
		}

		return null;
	}

	protected function Authenticate( Request $request )
	{
		$authenticationProvider = $this->GetAuthenticationProvider();

		if ( $authenticationProvider != null )
		{
			$response = $authenticationProvider->Authenticate( $request );

			if ( $response instanceof Response )
			{
				throw new ForceResponseException( $response );
			}

			if ( $response )
			{
				Log::Debug( "Authentication Succeeded", "RESTAPI" );
				return true;
			}

			Log::Warning( "Authentication Failed", "RESTAPI" );

			return false;
		}

		return true;
	}

	protected function GenerateResponseForRequest( $request = null, $currentUrlFragment = "" )
	{
		try
		{
			if ( !$this->Authenticate( $request ) )
			{
				return new NotAuthorisedResponse();
			}
		}
		catch( ForceResponseException $ex )
		{
			Log::Warning( "Authentication Failed: Forcing 401 Response", "RESTAPI" );
			return $ex->GetResponse();
		}

		$types = $this->GetSupportedMimeTypes();
		$methods = $this->GetSupportedHttpMethods();

		$typeString = strtolower( $request->Header( "HTTP_ACCEPT" ) );

		if ( preg_match( "/\*\/\*/", $typeString ) || $typeString == "" )
		{
			$typeString = "text/html";
		}

		$type = false;

		$method = strtolower( $request->Server( "REQUEST_METHOD" ) );

		if ( $method == "" )
		{
			$method = "get";
		}

		foreach( $types as $possibleType => $match )
		{
			if ( stripos( $typeString, $possibleType ) !== false )
			{
				$type = $possibleType;
				// First match wins
				break;
			}
		}

		if ( !$type )
		{
			return false;
		}

		if ( !isset( $types[ $type ] ) )
		{
			Log::Warning( "Rest url doesn't support ".$type, "RESTAPI" );
			return false;
		}

		// If GET is allowed then HEAD must also be allowed.
		if ( $method == "head" && !in_array( $method, $methods ) && in_array( "get", $methods ) )
		{
			$methods[] = "head";
		}

		if ( !in_array( $method, $methods ) )
		{
			Log::Warning( "Rest url doesn't support ".$method, "RESTAPI" );

			$this->HandleInvalidMethod( $method );
		}

		$correctMethodName = $method.$types[ $type ];

		if ( !method_exists( $this, $correctMethodName ) )
		{
			throw new RestImplementationException( "The REST end point `".$correctMethodName."` could not be found in handler `".get_class( $this )."`" );
		}

		return call_user_func( array( $this, $correctMethodName ), $request );
	}

	/**
	 * Override to handle the case where an HTTP method is unsupported.
	 *
	 * This should throw a ForceResponseException
	 *
	 * @param $method
	 * @throws \Rhubarb\Crown\Exceptions\ForceResponseException
	 */
	protected function HandleInvalidMethod( $method )
	{
		$emptyResponse = new Response();
		$emptyResponse->SetHeader( "HTTP/1.1 405 Method $method Not Allowed", false );
		$emptyResponse->SetHeader( "Allow", implode( ", ", $this->GetSupportedHttpMethods() ) );

		throw new ForceResponseException( $emptyResponse );
	}

	public function GenerateResponseForException(CoreException $er)
	{
		$date = new RhubarbDateTime( "now" );

		$response = new \stdClass();
		$response->result = new \stdClass();
		$response->result->status = false;
		$response->result->timestamp = $date->format( "c" );
		$response->result->message = $er->GetPrivateMessage();

		$json = new JsonResponse();
		$json->SetContent( $response );

		return $json;
	}
}