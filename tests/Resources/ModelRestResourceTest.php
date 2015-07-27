<?php

/*
 *	Copyright 2015 RhubarbPHP
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

namespace Rhubarb\RestApi\Tests\Resources;

use Rhubarb\Crown\Context;
use Rhubarb\Crown\Module;
use Rhubarb\Crown\Request\JsonRequest;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\RestApi\Authentication\AuthenticationProvider;
use Rhubarb\RestApi\Resources\ItemRestResource;
use Rhubarb\RestApi\Resources\ModelRestResource;
use Rhubarb\RestApi\UrlHandlers\RestApiRootHandler;
use Rhubarb\RestApi\UrlHandlers\RestCollectionHandler;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\Tests\Fixtures\Company;
use Rhubarb\Stem\Tests\Fixtures\Example;

class ModelRestResourceTest extends RhubarbTestCase
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

        SolutionSchema::registerSchema("restapi", '\Rhubarb\Stem\Tests\Fixtures\UnitTestingSolutionSchema');
        AuthenticationProvider::setDefaultAuthenticationProviderClassName("");

        ModelRestResource::registerModelToResourceMapping("Company", UnitTestCompanyRestResource::class);
        ModelRestResource::registerModelToResourceMapping("Example",
            UnitTestExampleRestResourceWithCompanyHeader::class);

    }

    public function testResourceIncludesModel()
    {
        $request = new WebRequest();
        $request->Server("HTTP_ACCEPT", "application/json");
        $request->Server("REQUEST_METHOD", "get");
        $request->UrlPath = "/contacts/1";

        $rest = new RestCollectionHandler(__NAMESPACE__ . "\UnitTestExampleRestResource");
        $rest->setUrl("/contacts/");

        $response = $rest->GenerateResponse($request);
        $content = $response->GetContent();

        $this->assertEquals("Andrew", $content->Forename, "The rest handler is not loading the model");
        $this->assertEquals(1, $content->_id, "The rest handler is not loading the model");
    }

    public function testCollectionIsModelCollection()
    {
        $request = new WebRequest();
        $request->Server("HTTP_ACCEPT", "application/json");
        $request->Server("REQUEST_METHOD", "get");
        $request->UrlPath = "/contacts/";

        $rest = new RestCollectionHandler(__NAMESPACE__ . "\UnitTestExampleRestResource");
        $rest->setUrl("/contacts/");

        $response = $rest->GenerateResponse($request);
        $content = $response->GetContent();

        $this->assertEquals("Andrew", $content->items[0]->Forename, "The rest handler is not loading the collection");
        $this->assertEquals(1, $content->items[0]->_id, "The rest handler is not loading the collection");
    }

    public function testCollectionCountAndRanging()
    {
        Example::ClearObjectCache();

        for ($x = 0; $x < 110; $x++) {
            $example = new Example();
            $example->Forename = $x;
            $example->Surname = $x;
            $example->Save();
        }

        $request = new WebRequest();
        $request->Server("HTTP_ACCEPT", "application/json");
        $request->Server("REQUEST_METHOD", "get");
        $request->UrlPath = "/contacts/";

        $context = new Context();
        $context->Request = $request;

        $rest = new RestCollectionHandler(UnitTestExampleRestResource::class);
        $rest->setUrl("/contacts/");

        $response = $rest->GenerateResponse($request);
        $content = $response->GetContent();

        $this->assertEquals(110, $content->count, "The rest collection count is invalid");
        $this->assertEquals(0, $content->range->from, "The rest collection range is invalid");
        $this->assertEquals(99, $content->range->to, "The rest collection range is invalid");
        $this->assertCount(100, $content->items, "The rest collection range is invalid");
        $this->assertEquals(42, $content->items[42]->Forename, "The rest collection range is invalid");
        $this->assertEquals(48, $content->items[48]->Forename, "The rest collection range is invalid");

        $request->Server("HTTP_RANGE", "40-49");

        $response = $rest->GenerateResponse($request);
        $content = $response->GetContent();

        $this->assertEquals(110, $content->count, "The rest collection count is invalid");
        $this->assertEquals(40, $content->range->from, "The rest collection range is invalid");
        $this->assertEquals(49, $content->range->to, "The rest collection range is invalid");
        $this->assertEquals(42, $content->items[2]->Forename, "The rest collection range is invalid");
        $this->assertEquals(48, $content->items[8]->Forename, "The rest collection range is invalid");

        $request->Server("HTTP_RANGE", "");
    }

    public function testResourceCanBeUpdated()
    {
        $changes = ["Forename" => "Johnny"];

        $context = new Context();
        $context->SimulatedRequestBody = json_encode($changes);

        $request = new JsonRequest();
        $request->Server("HTTP_ACCEPT", "application/json");
        $request->Server("REQUEST_METHOD", "put");

        $context->Request = $request;
        $request->UrlPath = "/contacts/1";

        $rest = new RestCollectionHandler(UnitTestExampleRestResource::class);
        $rest->setUrl("/contacts/");

        $response = $rest->GenerateResponse($request);
        $content = $response->GetContent()->result;

        $example = Example::FindFirst();

        $this->assertEquals("Johnny", $example->Forename, "The put operation didn't update the model");
        $this->assertTrue($content->status);
        $this->assertContains("The PUT operation completed successfully", $content->message);
        $this->assertEquals(date("c"), $content->timestamp);
    }

    public function testResourceCanBeInserted()
    {
        $changes = ["Forename" => "Bobby", "Surname" => "Smith"];

        $context = new Context();
        $context->SimulatedRequestBody = json_encode($changes);

        $request = new JsonRequest();
        $request->Server("HTTP_ACCEPT", "application/json");
        $request->Server("REQUEST_METHOD", "post");

        $context->Request = $request;
        $request->UrlPath = "/contacts/";

        $rest = new RestCollectionHandler(UnitTestExampleRestResource::class);
        $rest->setUrl("/contacts/");

        Example::ClearObjectCache();

        $response = $rest->GenerateResponse($request);
        $content = $response->GetContent();

        $example = Example::FindFirst();

        $this->assertEquals("Bobby", $example->Forename, "The post operation didn't update the model");
        $this->assertEquals("Smith", $example->Surname, "The post operation didn't update the model");
        $this->assertEquals("Bobby", $content->Forename);
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
        $request->Server("HTTP_ACCEPT", "application/json");
        $request->Server("REQUEST_METHOD", "delete");

        $context->Request = $request;
        $request->UrlPath = "/contacts/" . $example->UniqueIdentifier;

        $rest = new RestCollectionHandler(UnitTestExampleRestResource::class);
        $rest->setUrl("/contacts/");

        $response = $rest->GenerateResponse($request);
        $content = $response->GetContent();

        $this->assertCount(1, Example::Find());
        $this->assertEquals("Jerry", Example::FindFirst()->Forename);
        $this->assertTrue($content->result->status);

        $this->assertContains("The DELETE operation completed successfully", $content->result->message);
    }

    public function testCustomColumns()
    {
        $context = new Context();

        $request = new JsonRequest();
        $request->Server("HTTP_ACCEPT", "application/json");
        $request->Server("REQUEST_METHOD", "get");

        $context->Request = $request;
        $request->UrlPath = "/contacts/1";

        $rest = new RestCollectionHandler(UnitTestExampleRestResource::class);
        $rest->setUrl("/contacts/");

        $response = $rest->GenerateResponse($request);
        $content = $response->GetContent();

        $this->assertEquals("Andrew", $content->Forename);
        $this->assertEquals("Grasswisperer", $content->Surname);

        $this->assertTrue(isset($content->Company));
        $this->assertNotInstanceOf(\Rhubarb\Stem\Models\Model::class, $content->Company);
        $this->assertEquals("Big Widgets", $content->Company->CompanyName);
    }

    public function testHeadLinks()
    {
        $context = new Context();

        $request = new JsonRequest();
        $request->Server("HTTP_ACCEPT", "application/json");
        $request->Server("REQUEST_METHOD", "get");

        $context->Request = $request;
        $request->UrlPath = "/contacts/1";

        $companyRest = new RestCollectionHandler(UnitTestCompanyRestResource::class);
        $companyRest->setUrl("/companies/");

        $rest = new RestCollectionHandler(UnitTestExampleRestResourceWithCompanyHeader::class);
        $rest->setUrl("/contacts/");

        $response = $rest->GenerateResponse($request);
        $content = $response->GetContent();

        $this->assertTrue(isset($content->Company));
        $this->assertFalse(isset($content->Company->Balance));

        $request = new JsonRequest();
        $request->Server("HTTP_ACCEPT", "application/json");
        $request->Server("REQUEST_METHOD", "get");

        $context->Request = $request;
        $request->UrlPath = "/companies/1";

        $response = $companyRest->GenerateResponse($request);
        $company = $response->GetContent();

        $this->assertEquals("Big Widgets", $company->CompanyName);
        $this->assertTrue(isset($company->Contacts));
    }

    public function testUrlsAreSet()
    {
        $request = new WebRequest();
        $request->Header("HTTP_ACCEPT", "application/json");
        $request->Server("REQUEST_METHOD", "get");
        $request->Server("SERVER_PORT", 80);
        $request->Server("HTTP_HOST", "cli");
        $request->UrlPath = "/contacts/1";

        $rest = new RestCollectionHandler(UnitTestExampleRestResource::class);

        $api = new RestApiRootHandler(UnitTestDummyResource::class,
            [
                "contacts" => $rest
            ]);

        $api->setUrl("/");

        $context = new Context();
        $context->Request = $request;

        $response = $api->GenerateResponse($request);
        $content = $response->GetContent();

        $this->assertEquals("contacts/1", $content->_href);
    }

    public function testCollectionIsFiltered()
    {
        $request = new WebRequest();
        $request->Header("HTTP_ACCEPT", "application/json");
        $request->Server("REQUEST_METHOD", "get");
        $request->Server("SERVER_PORT", 80);
        $request->Server("HTTP_HOST", "cli");
        $request->UrlPath = "/companies/1/contacts";

        Module::ClearModules();
        Module::RegisterModule(new UnitTestRestModule());
        Module::InitialiseModules();

        $context = new Context();
        $context->Request = $request;

        $response = Module::GenerateResponseForRequest($request);

        $content = $response->GetContent();

        $this->assertCount(1, $content->items);
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
                "/companies" => new RestCollectionHandler(UnitTestCompanyRestResource::class,
                    [
                        "contacts" => new RestCollectionHandler(UnitTestExampleRestResource::class)
                    ])
            ]);
    }
}

class UnitTestDummyResource extends ItemRestResource
{

}

class UnitTestExampleRestResourceCustomisedColumns extends ModelRestResource
{
    protected function getColumns()
    {
        return ["Forename", "Company"];
    }


    /**
     * Returns the name of the model to use for this resource.
     *
     * @return string
     */
    public function getModelName()
    {
        return "Example";
    }
}

class UnitTestExampleRestResourceWithCompanyHeader extends ModelRestResource
{
    protected function getColumns()
    {
        return ["Forename", "Company:summary"];
    }

    /**
     * Returns the name of the model to use for this resource.
     *
     * @return string
     */
    public function getModelName()
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
    public function getModelName()
    {
        return "Example";
    }

    protected function getColumns()
    {
        $columns = parent::getColumns();
        $columns[] = "Surname";
        $columns[] = "Company";

        return $columns;
    }
}

class UnitTestCompanyRestResource extends ModelRestResource
{
    protected function getColumns()
    {
        return ["CompanyName", "Contacts"];
    }

    protected function getSummary()
    {
        return ["CompanyName"];
    }

    /**
     * Returns the name of the model to use for this resource.
     *
     * @return string
     */
    public function getModelName()
    {
        return "Company";
    }
}