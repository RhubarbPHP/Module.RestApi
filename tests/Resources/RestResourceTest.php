<?php

namespace Rhubarb\Crown\RestApi\Resources;

use Rhubarb\Crown\Context;
use Rhubarb\Crown\Request\JsonRequest;
use Rhubarb\Crown\RestApi\UrlHandlers\RestCollectionHandler;
use Rhubarb\Crown\UnitTesting\CoreTestCase;

class RestResourceTest extends CoreTestCase
{
	public function testRestPayloadValidationForModelResources()
	{
		include_once(__DIR__ . "/ModelRestResourceTest.php");

		$request = new JsonRequest();
		$request->Server( "HTTP_ACCEPT", "application/json" );
		$request->Server( "REQUEST_METHOD", "post" );
		$request->UrlPath = "/contacts/";

		$context = new Context();
		$context->Request = $request;
		$context->SimulatedRequestBody = null;

		$rest = new RestCollectionHandler( __NAMESPACE__."\UnitTestExampleRestResource" );
		$rest->setUrl( "/contacts/" );

		$response = $rest->GenerateResponse( $request );
		$content = $response->GetContent();

		$this->assertFalse( $content->result->status, "POST requests with no payload should fail" );

		$stdClass = new \stdClass();
		$stdClass->a = "b";

		$context->SimulatedRequestBody = json_encode( $stdClass );

		$response = $rest->GenerateResponse( $request );
		$content = $response->GetContent();

		$this->assertEquals( "", $content->Forename, "Posting to this collection should return the new resource.");

		$context->SimulatedRequestBody = "";
	}
}
 