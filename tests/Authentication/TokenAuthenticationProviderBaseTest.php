<?php

namespace Rhubarb\Crown\RestApi\Authentication;

use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\RestApi\Resources\RestResource;
use Rhubarb\Crown\RestApi\UrlHandlers\RestResourceHandler;
use Rhubarb\Crown\UnitTesting\CoreTestCase;

class TokenAuthenticationProviderBaseTest extends CoreTestCase
{
	protected function setUp()
	{
		parent::setUp();

		AuthenticationProvider::setDefaultAuthenticationProviderClassName( "\Rhubarb\Crown\RestApi\Authentication\TokenAuthenticationTestAuthenticationProvider" );
	}

	public function testTokenRequested()
	{
		$request = new WebRequest();
		$request->Server( "HTTP_ACCEPT", "application/json" );
		$request->Server( "REQUEST_METHOD", "get" );
		$request->UrlPath = "/contacts/";

		$rest = new RestResourceHandler( __NAMESPACE__."\TokenAuthenticationTestResource" );
		$rest->setUrl( "/contacts/" );

		$response = $rest->GenerateResponse( $request );
		$headers = $response->GetHeaders();

		$this->assertArrayHasKey( "WWW-authenticate", $headers );

		$request->Header( "Authorization", "Token token=\"abc123\"" );

		$response = $rest->GenerateResponse( $request );
		$headers = $response->GetHeaders();

		$this->assertArrayNotHasKey( "WWW-authenticate", $headers );
	}

	protected function tearDown()
	{
		parent::tearDown();

		AuthenticationProvider::setDefaultAuthenticationProviderClassName( "" );
	}
}

class TokenAuthenticationTestAuthenticationProvider extends TokenAuthenticationProviderBase
{
	/**
	 * Returns true if the token is valid.
	 *
	 * @param $tokenString
	 * @return mixed
	 */
	protected function isTokenValid($tokenString)
	{
		return true;
	}
}

class TokenAuthenticationTestResource extends RestResource
{

}

