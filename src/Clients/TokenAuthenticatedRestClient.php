<?php

namespace Rhubarb\Crown\RestApi\Clients;
use Rhubarb\Crown\Integration\Http\HttpRequest;
use Rhubarb\Crown\RestApi\Exceptions\RestAuthenticationException;
use Rhubarb\Crown\RestApi\Exceptions\RestImplementationException;

/**
 * Extends the BasicAuthenticatedRestClient by adding support for tokens after the first
 * basic authenticated request to get a new token.
 */
class TokenAuthenticatedRestClient extends BasicAuthenticatedRestClient
{
	protected $_tokensUri = "";

	protected $_token = "";

	/**
	 * @var bool True if the client is busy getting the authentication token.
	 */
	protected $_gettingToken = false;

	public function __construct($apiUrl, $username, $password, $tokensUri, $existingToken = "" )
	{
		parent::__construct( $apiUrl, $username, $password );

		$this->_tokensUri = $tokensUri;
		$this->_token = $existingToken;
	}

	/**
	 * For long duration API conversations the token can be persisted externally and set using this method.
	 *
	 * @param $token
	 */
	public function SetToken( $token )
	{
		$this->_token = $token;
	}

	protected function ApplyAuthenticationDetailsToRequest( HttpRequest $request )
	{
		if ( $this->_gettingToken )
		{
			parent::ApplyAuthenticationDetailsToRequest( $request );
			return;
		}

		if ( $this->_token == "" )
		{
			$this->GetToken();
		}

		$request->AddHeader( "Authorization", "Token token=\"".$this->_token."\"/" );
	}

	/**
	 * A placeholder to be overriden usually to store the token in a session or somewhere similar
	 *
	 * @param $token
	 */
	protected function OnTokenReceived( $token )
	{

	}

	protected final function GetToken()
	{
		$this->_gettingToken = true;

		$response = $this->MakeRequest( new RestHttpRequest( $this->_tokensUri, "post", "" ) );

		$this->_gettingToken = false;

		if ( !is_object( $response ) )
		{
			throw new RestAuthenticationException( "The api credentials were rejected." );
		}

		$this->_token = $response->token;

		$this->OnTokenReceived( $this->_token );
	}
}