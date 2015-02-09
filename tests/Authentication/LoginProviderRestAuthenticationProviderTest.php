<?php

namespace Rhubarb\Crown\RestApi\Authentication;

use Rhubarb\Crown\Encryption\HashProvider;
use Rhubarb\Stem\LoginProviders\ModelLoginProvider;
use Rhubarb\Stem\UnitTesting\User;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\RestApi\Resources\RestResource;
use Rhubarb\Crown\RestApi\UrlHandlers\RestCollectionHandler;
use Rhubarb\Crown\RestApi\UrlHandlers\RestHandler;
use Rhubarb\Crown\RestApi\UrlHandlers\RestResourceHandler;
use Rhubarb\Crown\UnitTesting\CoreTestCase;

class LoginProviderRestAuthenticationProviderTest extends CoreTestCase
{
	protected function setUp()
	{
		parent::setUp();

		User::ClearObjectCache();

		HashProvider::SetHashProviderClassName( "\Rhubarb\Crown\Encryption\PlainTextHashProvider" );

		AuthenticationProvider::setDefaultAuthenticationProviderClassName( "\Rhubarb\Crown\RestApi\Authentication\UnitTestLoginProviderRestAuthenticationProvider" );

		$user = new User();
		$user->Username = "bob";
		$user->Password = "smith";
		$user->Active = 1;
		$user->Save();
	}

	protected function tearDown()
	{
		parent::tearDown();

		AuthenticationProvider::setDefaultAuthenticationProviderClassName( "" );
	}

	public function testAuthenticationProviderWorks()
	{
		$request = new WebRequest();
		$request->Server( "HTTP_ACCEPT", "application/json" );
		$request->Server( "REQUEST_METHOD", "get" );
		$request->UrlPath = "/contacts/";

		$rest = new RestResourceHandler( __NAMESPACE__."\RestAuthenticationTestResource" );
		$rest->SetUrl( "/contacts/" );

		$response = $rest->GenerateResponse( $request );
		$headers = $response->GetHeaders();

		$this->assertArrayHasKey( "WWW-authenticate", $headers );

		$this->assertContains( "Basic", $headers[ "WWW-authenticate" ] );
		$this->assertContains( "realm=\"API\"", $headers[ "WWW-authenticate" ] );

		// Supply the credentials
		$request->Header( "Authorization", "Basic ".base64_encode( "bob:smith" ) );

		$response = $rest->GenerateResponse( $request );
		$headers = $response->GetHeaders();

		$this->assertArrayNotHasKey( "WWW-authenticate", $headers );
		$content = $response->GetContent();

		$this->assertTrue( $content->authenticated );

		// Incorrect credentials.
		$request->Header( "Authorization", "Basic ".base64_encode( "terry:smith" ) );

		$response = $rest->GenerateResponse( $request );
		$headers = $response->GetHeaders();

		$this->assertArrayHasKey( "WWW-authenticate", $headers );
	}
}

class UnitTestLoginProviderRestAuthenticationProvider extends ModelLoginProviderAuthenticationProvider
{
	protected function GetLoginProviderClassName()
	{
		return "\Rhubarb\Crown\RestApi\Authentication\RestAuthenticationTestLoginProvider";
	}
}

class RestAuthenticationTestResource extends RestResource
{
	public function Get( RestHandler $handler = null )
	{
		$response = parent::Get( $handler );
		$response->authenticated = true;

		return $response;
	}
}

class RestAuthenticationTestLoginProvider extends ModelLoginProvider
{
	public function __construct()
	{
		parent::__construct(
			"\Rhubarb\Stem\UnitTesting\User",
			"Username",
			"Password",
			"Active");
	}
}