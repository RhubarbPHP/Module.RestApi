<?php

namespace Rhubarb\Crown\RestApi\Authentication;

use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\RestApi\Response\TokenAuthorisationRequiredResponse;

/**
 * An abstract authentication provider that understands how to parse the Authorization HTTP header for a token.
 *
 * Users should extend the class and implement the IsTokenValid function to implement the testing of the token
 * string.
 */
abstract class TokenAuthenticationProviderBase extends AuthenticationProvider
{
	/**
	 * Returns true if the token is valid.
	 *
	 * @param $tokenString
	 * @return mixed
	 */
	protected abstract function IsTokenValid( $tokenString );

	public function authenticate( Request $request )
	{
		if ( !$request->Header( "Authorization" ) )
		{
			throw new ForceResponseException( new TokenAuthorisationRequiredResponse() );
		}

		$authString = trim( $request->Header( "Authorization" ) );

		if ( stripos( $authString, "token" ) !== 0 )
		{
			throw new ForceResponseException( new TokenAuthorisationRequiredResponse() );
		}

		if ( !preg_match( "/token=\"?([[:alnum:]]+)\"?/", $authString, $match ) )
		{
			throw new ForceResponseException( new TokenAuthorisationRequiredResponse() );
		}

		$token = $match[1];

		if ( !$this->IsTokenValid( $token ) )
		{
			throw new ForceResponseException( new TokenAuthorisationRequiredResponse() );
		}

		return true;
	}
}