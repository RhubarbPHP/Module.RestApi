<?php

namespace Rhubarb\Crown\RestApi\Clients;

use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Crown\Integration\Http\HttpRequest;

/**
 * A version of the HttpRequest that allows just the URI to be set and combined with a ReST stub URL.
 *
 */
class RestHttpRequest extends HttpRequest
{
	private $_uri;

	private $_apiUrl;

	public function __construct( $uri, $method = "get", $payload = null )
	{
		$this->SetUri( $uri );
		$this->SetMethod( $method );
		$this->SetPayload( json_encode( $payload ) );

		$this->AddHeader( "Content-Type", "application/json" );

		// Note we don't call the parent constructor as it will try and set the $_url property which isn't
		// valid for RestHttpRequests
		if ( $method == "post" || $method == "put" )
		{
			$this->AddHeader( "Content-Length", strlen( $this->getPayload() ) );
		}
	}

	/**
	 * @param mixed $apiUrl
	 */
	public function SetApiUrl($apiUrl)
	{
		$this->_apiUrl = $apiUrl;
	}

	/**
	 * @return mixed
	 */
	public function GetApiUrl()
	{
		return $this->_apiUrl;
	}

	/**
	 * @param mixed $uri
	 */
	public function SetUri($uri)
	{
		$this->_uri = $uri;
	}

	/**
	 * @return mixed
	 */
	public function GetUri()
	{
		return $this->_uri;
	}

	public function SetUrl( $url )
	{
		throw new ImplementationException( "A RestHttpRequest does not support setting the Url directly. Set the Uri and ApiUrl properties separately." );
	}

	public function getUrl()
	{
		$url = rtrim( $this->_apiUrl, '/' )."/".ltrim( $this->_uri, '/' );

		return $url;
	}
} 