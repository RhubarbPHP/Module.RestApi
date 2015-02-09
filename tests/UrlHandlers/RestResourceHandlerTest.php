<?php

namespace Rhubarb\Crown\RestApi\UrlHandlers;

use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\RestApi\Exceptions\RestImplementationException;
use Rhubarb\Crown\RestApi\Exceptions\RestRequestPayloadValidationException;
use Rhubarb\Crown\RestApi\Resources\RestResource;
use Rhubarb\Crown\UnitTesting\CoreTestCase;

class RestResourceHandlerTest extends CoreTestCase
{
	public function testHandlerGetsResource()
	{
		$restHandler = new RestResourceHandler( "Rhubarb\Crown\RestApi\UnitTesting\UnitTestingRestResource" );

		$request = new WebRequest();
		$request->Server( "HTTP_ACCEPT", "application/json" );
		$request->Server( "REQUEST_METHOD", "get" );
		$request->UrlPath = "/anything/test";

		$restHandler->setUrl( "/anything/test" );

		$response = $restHandler->GenerateResponse( $request );
		$content = $response->GetContent();

		$this->assertEquals( "constructed", $content->value, "The rest handler is not instantiating the resource" );
	}

	public function testValidationOfPayloads()
	{
		$restHandler = new RestResourceHandler( "Rhubarb\Crown\RestApi\UrlHandlers\ValidatedPayloadTestRestResource", [], [ "post" ] );

		$request = new WebRequest();
		$request->Header( "HTTP_ACCEPT", "application/json" );
		$request->Server( "REQUEST_METHOD", "post" );
		$request->UrlPath = "/anything/test";

		$restHandler->setUrl( "/anything/test" );

		$response = $restHandler->GenerateResponse( $request );
		$content = $response->GetContent();

		$this->assertFalse( $content->result->status );
		$this->assertEquals( "The request payload isn't valid", $content->result->message );
	}
}

class ValidatedPayloadTestRestResource extends RestResource
{
	public function validateRequestPayload($payload, $method)
	{
		throw new RestRequestPayloadValidationException( "The request payload isn't valid" );
	}

	public function post($restResource, RestHandler $handler = null)
	{
		// Simply return an empty resource for now.
		return $this->get( $handler );
	}

	public function put($restResource, RestHandler $handler = null)
	{
		return $this->post( $restResource, $handler );
	}


}