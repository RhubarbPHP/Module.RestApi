<?php


namespace Rhubarb\Crown\UrlHandlers;

use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\RestApi\UrlHandlers\RestCollectionHandler;
use Rhubarb\Crown\UnitTesting\CoreTestCase;


class RestCollectionHandlerTest extends CoreTestCase
{
	public function testUrlMatching()
	{
		$request = new WebRequest();
		$request->Server( "HTTP_ACCEPT", "application/json" );
		$request->Server( "REQUEST_METHOD", "get" );
		$request->UrlPath = "/users/";

		$rest = new UnitTestRestCollectionHandler();
		$rest->setUrl( "/users/" );

		$response = $rest->GenerateResponse( $request );
		$content = $response->GetContent();

		$this->assertEquals( "collection", $content, "The rest handler is not recognising the collection" );

		$request->UrlPath = "/users/1/";

		$response = $rest->GenerateResponse( $request );
		$content = $response->GetContent();

		$this->assertEquals( "constructed", $content->value, "The rest handler is not instantiating the resource" );
	}
}

class UnitTestRestCollectionHandler extends RestCollectionHandler
{
	public function __construct( $childUrlHandlers = [ ] )
	{
		parent::__construct( "\Rhubarb\Crown\RestApi\UnitTesting\UnitTestingRestResource", $childUrlHandlers );
	}
}