<?php

namespace Rhubarb\Crown\RestApi\Clients;
use Rhubarb\Crown\Integration\Http\HttpRequest;

/**
 * Extends RestClient by adding support for HTTP basic authentication.
 */
class BasicAuthenticatedRestClient extends RestClient
{
	protected $_username;
	protected $_password;

	public function __construct( $apiUrl, $username, $password )
	{
		parent::__construct($apiUrl);

		$this->_username = $username;
		$this->_password = $password;
	}

	protected function ApplyAuthenticationDetailsToRequest( HttpRequest $request )
	{
		$request->AddHeader( "Authorization", "Basic ".base64_encode( $this->_username.":".$this->_password ) );
	}
} 