<?php

namespace Rhubarb\Crown\RestApi\Resources;

use Rhubarb\Crown\Context;
use Rhubarb\Crown\CoreModule;
use Rhubarb\Stem\UnitTesting\Company;
use Rhubarb\Stem\UnitTesting\Example;
use Rhubarb\Crown\Module;
use Rhubarb\Crown\Request\JsonRequest;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\RestApi\Authentication\AuthenticationProvider;
use Rhubarb\Crown\RestApi\UrlHandlers\RestApiRootHandler;
use Rhubarb\Crown\RestApi\UrlHandlers\RestCollectionHandler;
use Rhubarb\Crown\UnitTesting\CoreTestCase;

class ModelRestResourceTest extends CoreTestCase
{
	protected function setUp()
	{
		parent::setUp();

		Company::ClearObjectCache();
		Example::ClearObjectCache();

		$company = new Company();
		$company->CompanyName = "Big Widgets";
		$company->Save();

		$example = new Example();
		$example->Forename = "Andrew";
		$example->Surname = "Grasswisperer";
		$example->CompanyID = $company->UniqueIdentifier;
		$example->Save();

        $example = new Example();
        $example->Forename = "Billy";
        $example->Surname = "Bob";
        $example->CompanyID = $company->UniqueIdentifier + 1;
        $example->Save();

        $example = new Example();
        $example->Forename = "Mary";
        $example->Surname = "Smith";
        $example->CompanyID = $company->UniqueIdentifier + 1;
        $example->Save();

		AuthenticationProvider::setDefaultAuthenticationProviderClassName( "" );
	}

	public function testResourceIncludesModel()
	{
		$request = new WebRequest();
		$request->Server( "HTTP_ACCEPT", "application/json" );
		$request->Server( "REQUEST_METHOD", "get" );
		$request->UrlPath = "/contacts/1";

		$rest = new RestCollectionHandler( __NAMESPACE__."\UnitTestExampleRestResource" );
		$rest->SetUrl( "/contacts/" );

		$response = $rest->GenerateResponse( $request );
		$content = $response->GetContent();

		$this->assertEquals( "Andrew", $content->Forename, "The rest handler is not loading the model" );
		$this->assertEquals( 1, $content->_id, "The rest handler is not loading the model" );
	}

	public function testCollectionIsModelCollection()
	{
		$request = new WebRequest();
		$request->Server( "HTTP_ACCEPT", "application/json" );
		$request->Server( "REQUEST_METHOD", "get" );
		$request->UrlPath = "/contacts/";

		$rest = new RestCollectionHandler( __NAMESPACE__."\UnitTestExampleRestResource" );
		$rest->SetUrl( "/contacts/" );

		$response = $rest->GenerateResponse( $request );
		$content = $response->GetContent();

		$this->assertEquals( "Andrew", $content->items[0]->Forename, "The rest handler is not loading the collection" );
		$this->assertEquals( 1, $content->items[0]->_id, "The rest handler is not loading the collection" );
	}

	public function testCollectionCountAndRanging()
	{
		Example::ClearObjectCache();

		for( $x = 0; $x < 110; $x++ )
		{
			$example = new Example();
			$example->Forename = $x;
			$example->Surname = $x;
			$example->Save();
		}

		$request = new WebRequest();
		$request->Server( "HTTP_ACCEPT", "application/json" );
		$request->Server( "REQUEST_METHOD", "get" );
		$request->UrlPath = "/contacts/";

		$context = new Context();
		$context->Request = $request;

		$rest = new RestCollectionHandler( __NAMESPACE__."\UnitTestExampleRestResource" );
		$rest->SetUrl( "/contacts/" );

		$response = $rest->GenerateResponse( $request );
		$content = $response->GetContent();

		$this->assertEquals( 110, $content->count, "The rest collection count is invalid" );
		$this->assertEquals( 0, $content->range->from, "The rest collection range is invalid" );
		$this->assertEquals( 99, $content->range->to, "The rest collection range is invalid" );
		$this->assertCount( 100, $content->items, "The rest collection range is invalid" );
		$this->assertEquals( 42, $content->items[42]->Forename, "The rest collection range is invalid" );
		$this->assertEquals( 48, $content->items[48]->Forename, "The rest collection range is invalid" );

		$request->Server( "HTTP_RANGE", "40-49" );

		$response = $rest->GenerateResponse( $request );
		$content = $response->GetContent();

		$this->assertEquals( 110, $content->count, "The rest collection count is invalid" );
		$this->assertEquals( 40, $content->range->from, "The rest collection range is invalid" );
		$this->assertEquals( 49, $content->range->to, "The rest collection range is invalid" );
		$this->assertEquals( 42, $content->items[2]->Forename, "The rest collection range is invalid" );
		$this->assertEquals( 48, $content->items[8]->Forename, "The rest collection range is invalid" );

        $request->Server( "HTTP_RANGE", "" );
	}

	public function testResourceCanBeUpdated()
	{
		$changes = [ "Forename" => "Johnny" ];

		$context = new Context();
		$context->SimulatedRequestBody = json_encode( $changes );

		$request = new JsonRequest();
		$request->Server( "HTTP_ACCEPT", "application/json" );
		$request->Server( "REQUEST_METHOD", "put" );

		$context->Request = $request;
		$request->UrlPath = "/contacts/1";

		$rest = new RestCollectionHandler( __NAMESPACE__."\UnitTestExampleRestResource" );
		$rest->SetUrl( "/contacts/" );

		$response = $rest->GenerateResponse( $request );
		$content = $response->GetContent()->result;

		$example = Example::FindFirst();

		$this->assertEquals( "Johnny", $example->Forename, "The put operation didn't update the model" );
		$this->assertTrue( $content->status );
		$this->assertContains( "The PUT operation completed successfully", $content->message );
		$this->assertEquals( date( "c" ), $content->timestamp );
	}

	public function testResourceCanBeInserted()
	{
		$changes = [ "Forename" => "Bobby", "Surname" => "Smith" ];

		$context = new Context();
		$context->SimulatedRequestBody = json_encode( $changes );

		$request = new JsonRequest();
		$request->Server( "HTTP_ACCEPT", "application/json" );
		$request->Server( "REQUEST_METHOD", "post" );

		$context->Request = $request;
		$request->UrlPath = "/contacts/";

		$rest = new RestCollectionHandler( __NAMESPACE__."\UnitTestExampleRestResource" );
		$rest->SetUrl( "/contacts/" );

		Example::ClearObjectCache();

		$response = $rest->GenerateResponse( $request );
		$content = $response->GetContent();

		$example = Example::FindFirst();

		$this->assertEquals( "Bobby", $example->Forename, "The post operation didn't update the model" );
		$this->assertEquals( "Smith", $example->Surname, "The post operation didn't update the model" );
		$this->assertEquals( "Bobby", $content->Forename );
	}

	public function testResourceCanBeDeleted()
	{
		Example::ClearObjectCache();

		$example = new Example();
		$example->Forename = "Jerry";
		$example->Surname = "Maguire";
		$example->Save();

		$example = new Example();
		$example->Forename = "Jolly";
		$example->Surname = "Bob";
		$example->Save();

		$context = new Context();

		$request = new JsonRequest();
		$request->Server( "HTTP_ACCEPT", "application/json" );
		$request->Server( "REQUEST_METHOD", "delete" );

		$context->Request = $request;
		$request->UrlPath = "/contacts/".$example->UniqueIdentifier;

		$rest = new RestCollectionHandler( __NAMESPACE__."\UnitTestExampleRestResource" );
		$rest->SetUrl( "/contacts/" );

		$response = $rest->GenerateResponse( $request );
		$content = $response->GetContent();

		$this->assertCount( 1, Example::Find() );
		$this->assertEquals( "Jerry", Example::FindFirst()->Forename );
		$this->assertTrue( $content->result->status );

		$this->assertContains( "The DELETE operation completed successfully", $content->result->message );
	}

	public function testCustomColumns()
	{
		ModelRestResource::RegisterModelToResourceMapping( "Company", "\Rhubarb\Crown\RestApi\Resources\UnitTestCompanyRestResource" );

		$context = new Context();

		$request = new JsonRequest();
		$request->Server( "HTTP_ACCEPT", "application/json" );
		$request->Server( "REQUEST_METHOD", "get" );

		$context->Request = $request;
		$request->UrlPath = "/contacts/1";

		$rest = new RestCollectionHandler( __NAMESPACE__."\UnitTestExampleRestResourceCustomisedColumns" );
		$rest->SetUrl( "/contacts/" );

		$response = $rest->GenerateResponse( $request );
		$content = $response->GetContent();

		$this->assertEquals( "Andrew", $content->Forename );
		$this->assertFalse( isset( $content->Surname ) );

		$this->assertTrue( isset( $content->Company ) );
		$this->assertNotInstanceOf( "Rhubarb\Stem\Models\Model", $content->Company );
		$this->assertEquals( "Big Widgets", $content->Company->CompanyName );
	}

	public function testHeadLinks()
	{
		ModelRestResource::RegisterModelToResourceMapping( "Company", "\Rhubarb\Crown\RestApi\Resources\UnitTestCompanyRestResource" );
		ModelRestResource::RegisterModelToResourceMapping( "Example", "\Rhubarb\Crown\RestApi\Resources\UnitTestExampleRestResourceWithCompanyHeader" );

		$context = new Context();

		$request = new JsonRequest();
		$request->Server( "HTTP_ACCEPT", "application/json" );
		$request->Server( "REQUEST_METHOD", "get" );

		$context->Request = $request;
		$request->UrlPath = "/contacts/1";

		$companyRest = new RestCollectionHandler( __NAMESPACE__."\UnitTestCompanyRestResource" );
		$companyRest->SetUrl( "/companies/" );

		$rest = new RestCollectionHandler( __NAMESPACE__."\UnitTestExampleRestResourceWithCompanyHeader" );
		$rest->SetUrl( "/contacts/" );

		$response = $rest->GenerateResponse( $request );
		$content = $response->GetContent();

		$this->assertTrue( isset( $content->Company ) );
		$this->assertFalse( isset( $content->Company->Balance ) );

		$request = new JsonRequest();
		$request->Server( "HTTP_ACCEPT", "application/json" );
		$request->Server( "REQUEST_METHOD", "get" );

		$context->Request = $request;
		$request->UrlPath = "/companies/1";

		$response = $companyRest->GenerateResponse( $request );
		$company = $response->GetContent();

		$this->assertEquals( "Big Widgets", $company->CompanyName );
		$this->assertTrue( isset( $company->Contacts ) );
	}

    public function testUrlsAreSet()
    {
        $request = new WebRequest();
        $request->Header( "HTTP_ACCEPT", "application/json" );
        $request->Server( "REQUEST_METHOD", "get" );
        $request->Server( "SERVER_PORT", 80 );
        $request->Server( "HTTP_HOST", "cli" );
        $request->UrlPath = "/contacts/1";

        $rest = new RestCollectionHandler( __NAMESPACE__."\UnitTestExampleRestResource" );

        $api = new RestApiRootHandler(  __NAMESPACE__."\UnitTestDummyResource",
            [
                "contacts" => $rest
            ] );

        $api->SetUrl( "/" );

        $context = new Context();
        $context->Request = $request;

        $response = $api->GenerateResponse( $request );
        $content = $response->GetContent();

        $this->assertEquals( "http://cli/contacts/1", $content->_href );
    }

    public function testCollectionIsFiltered()
    {
        $request = new WebRequest();
        $request->Header( "HTTP_ACCEPT", "application/json" );
        $request->Server( "REQUEST_METHOD", "get" );
        $request->Server( "SERVER_PORT", 80 );
        $request->Server( "HTTP_HOST", "cli" );
        $request->UrlPath = "/companies/1/contacts";

        Module::ClearModules();
        Module::RegisterModule( new CoreModule() );
        Module::RegisterModule( new UnitTestRestModule() );
        Module::InitialiseModules();

        $context = new Context();
        $context->Request = $request;

        $response = Module::GenerateResponseForRequest( $request );

        $content = $response->GetContent();

        $this->assertCount( 1, $content->items );
    }
}

class UnitTestRestModule extends Module
{
    public function __construct()
    {
        parent::__construct();

        $this->namespace = __NAMESPACE__;
    }

    protected function Initialise()
    {
        parent::Initialise();

        $this->AddUrlHandlers(
        [
            "/companies" => new RestCollectionHandler( __NAMESPACE__."\UnitTestCompanyRestResource",
            [
                "contacts" => new RestCollectionHandler( __NAMESPACE__."\UnitTestExampleRestResource" )
            ])
        ]);
    }
}

class UnitTestDummyResource extends RestResource
{

}

class UnitTestExampleRestResourceCustomisedColumns extends ModelRestResource
{
    protected function GetColumns()
    {
        return [ "Forename", "Company" ];
    }


    /**
     * Returns the name of the model to use for this resource.
     *
     * @return string
     */
    public function GetModelName()
    {
        return "Example";
    }
}

class UnitTestExampleRestResourceWithCompanyHeader extends ModelRestResource
{
    protected function GetColumns()
    {
        return [ "Forename", "Company:summary" ];
    }

    /**
     * Returns the name of the model to use for this resource.
     *
     * @return string
     */
    public function GetModelName()
    {
        return "Example";
    }
}

class UnitTestExampleRestResource extends ModelRestResource
{
    /**
     * Returns the name of the model to use for this resource.
     *
     * @return string
     */
    public function GetModelName()
    {
        return "Example";
    }
}

class UnitTestCompanyRestResource extends ModelRestResource
{
    protected function GetColumns()
    {
        return [ "CompanyName", "Contacts" ];
    }

    /**
     * Returns the name of the model to use for this resource.
     *
     * @return string
     */
    public function GetModelName()
    {
        return "Company";
    }
}