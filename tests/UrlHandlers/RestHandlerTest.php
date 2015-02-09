<?php

namespace Rhubarb\Crown\RestApi\UrlHandlers;

use Rhubarb\Crown\Context;
use Rhubarb\Crown\CoreModule;
use Rhubarb\Crown\Encryption\HashProvider;
use Rhubarb\Crown\Exceptions\CoreException;
use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\Integration\Email\EmailProvider;
use Rhubarb\Crown\Integration\IntegrationModule;
use Rhubarb\Crown\Layout\LayoutModule;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Crown\Module;
use Rhubarb\Leaf\MvpModule;
use Rhubarb\Crown\Patterns\PatternsModule;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\RestApi\Authentication\AuthenticationProvider;
use Rhubarb\Crown\RestApi\Resources\RestResource;
use Rhubarb\Crown\Scaffolds\AuthenticationWithRoles\AuthenticationWithRolesModule;
use Rhubarb\Crown\Scaffolds\NavigationMenu\NavigationMenuModule;
use Rhubarb\Crown\UnitTesting\CoreTestCase;
use Rhubarb\Crown\UnitTesting\UnitTestingModule;
use Rhubarb\Crown\UnitTesting\UnitTestingRestHandler;

class RestHandlerTest extends CoreTestCase
{
	/**
	 * @var UnitTestingRestHandler
	 */
	private $unitTestRestHandler;

	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		Module::RegisterModule( new UnitTestRestModule() );
		Module::InitialiseModules();
	}

	protected function setUp()
	{
		parent::setUp();

		$this->unitTestRestHandler = new UnitTestingRestHandler();
	}

	public function testMethodsCalledCorrectly()
	{
		$request = new WebRequest();

		$request->Header( "HTTP_ACCEPT", "image/jpeg" );
		$response = $this->unitTestRestHandler->GenerateResponse( $request );
		$this->assertFalse( $response, "image/jpeg should not be handled by this handler" );

		$request->Header( "HTTP_ACCEPT", "text/html" );
		$request->Server( "REQUEST_METHOD", "options" );

		try
		{
			$this->unitTestRestHandler->GenerateResponse( $request );
			$this->fail( "HTTP OPTIONS should not be handled by this handler" );
		}
		catch( ForceResponseException $er ){}

		// Check that */* is treated as text/html
		$request->Header( "HTTP_ACCEPT", "*/*" );
		$request->Server( "REQUEST_METHOD", "get" );

		$this->unitTestRestHandler->GenerateResponse( $request );
		$this->assertTrue( $this->unitTestRestHandler->getHtml );

		$request->Header( "HTTP_ACCEPT", "text/html" );
		$request->Server( "REQUEST_METHOD", "get" );

		$this->unitTestRestHandler->GenerateResponse( $request );
		$this->assertTrue( $this->unitTestRestHandler->getHtml );

		$request->Server( "REQUEST_METHOD", "post" );

		$this->unitTestRestHandler->GenerateResponse( $request );
		$this->assertTrue( $this->unitTestRestHandler->postHtml );

		$request->Server( "REQUEST_METHOD", "put" );

		$this->unitTestRestHandler->GenerateResponse( $request );
		$this->assertTrue( $this->unitTestRestHandler->putHtml );

		$request->Header( "HTTP_ACCEPT", "application/json" );
		$request->Server( "REQUEST_METHOD", "get" );

		$this->unitTestRestHandler->GenerateResponse( $request );
		$this->assertTrue( $this->unitTestRestHandler->getJson );

		$request->Server( "REQUEST_METHOD", "post" );

		$this->unitTestRestHandler->GenerateResponse( $request );
		$this->assertTrue( $this->unitTestRestHandler->postJson );

		$request->Server( "REQUEST_METHOD", "put" );

		$this->setExpectedException( "Rhubarb\Crown\RestApi\Exceptions\RestImplementationException" );

		$this->unitTestRestHandler->GenerateResponse( $request );
	}

	public function testRestHandlerFormatsExceptionsCorrectly()
	{
		$request = new WebRequest();
		$request->UrlPath = "/rest-test/";

		$response = Module::GenerateResponseForRequest( $request );

		$this->assertInstanceOf( '\Rhubarb\Crown\Response\JsonResponse', $response );

		$this->assertEquals( "Sorry, something went wrong and we couldn't complete your request. The developers have
been notified.", $response->GetContent()->result->message );
	}
}

class UnitTestRestModule extends Module
{
	public function __construct()
	{
		$this->namespace = __NAMESPACE__;

		parent::__construct();
	}

	protected function RegisterUrlHandlers()
	{
		$this->AddUrlHandlers(
			[
				"/rest-test/" => $url = new RestResourceHandler( '\Rhubarb\Crown\RestApi\UrlHandlers\UnitTestRestExceptionResource' )
			]
		);

		$url->SetPriority( 100 );
	}
}

class UnitTestRestExceptionResource extends RestResource
{
	public function get(RestHandler $handler = null)
	{
		throw new CoreException( "Somethings crashed" );
	}
}