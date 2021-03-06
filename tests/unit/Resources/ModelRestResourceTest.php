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

use Rhubarb\Crown\Application;
use Rhubarb\Crown\Context;
use Rhubarb\Crown\Module;
use Rhubarb\Crown\Request\JsonRequest;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\RestApi\Authentication\AuthenticationProvider;
use Rhubarb\RestApi\Resources\ItemRestResource;
use Rhubarb\RestApi\Resources\ModelRestResource;
use Rhubarb\RestApi\UrlHandlers\RestApiRootHandler;
use Rhubarb\RestApi\UrlHandlers\RestCollectionHandler;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\User;

class ModelRestResourceTest extends RhubarbTestCase
{
    protected function setUp()
    {
        parent::setUp();

        SolutionSchema::registerSchema("restapi", '\Rhubarb\Stem\Tests\unit\Fixtures\UnitTestingSolutionSchema');

        ModelRestResource::registerModelToResourceMapping("Company", UnitTestCompanyRestResource::class);
        ModelRestResource::registerModelToResourceMapping("UnitTestUser",
            UnitTestExampleRestResourceWithCompanyHeader::class);

        Company::clearObjectCache();
        User::clearObjectCache();

        $company = new Company();
        $company->CompanyName = "Big Widgets";
        $company->save();

        $example = new User();
        $example->Forename = "Andrew";
        $example->Surname = "Grasswisperer";
        $example->CompanyID = $company->UniqueIdentifier;
        $example->save();

        $example = new User();
        $example->Forename = "Billy";
        $example->Surname = "Bob";
        $example->CompanyID = $company->UniqueIdentifier + 1;
        $example->save();

        $example = new User();
        $example->Forename = "Mary";
        $example->Surname = "Smith";
        $example->CompanyID = $company->UniqueIdentifier + 1;
        $example->save();
    }

    public function testResourceIncludesModel()
    {
        $request = new WebRequest();
        $request->serverData["HTTP_ACCEPT"] = "application/json";
        $request->serverData["REQUEST_METHOD"] = "get";
        $request->urlPath = "/contacts/1";

        $rest = new RestCollectionHandler(__NAMESPACE__ . "\UnitTestExampleRestResource");
        $rest->setUrl("/contacts/");

        $response = $rest->generateResponse($request);
        $content = $response->getContent();

        $this->assertEquals("Andrew", $content->Forename, "The rest handler is not loading the model");
        $this->assertEquals(1, $content->_id, "The rest handler is not loading the model");
    }

    public function testCollectionIsModelCollection()
    {
        $request = new WebRequest();
        $request->serverData["HTTP_ACCEPT"] = "application/json";
        $request->serverData["REQUEST_METHOD"] =  "get";
        $request->urlPath = "/contacts/";

        $rest = new RestCollectionHandler(__NAMESPACE__ . "\UnitTestExampleRestResource");
        $rest->setUrl("/contacts/");

        $response = $rest->generateResponse($request);
        $content = $response->getContent();

        $this->assertEquals("Andrew", $content->items[0]->Forename, "The rest handler is not loading the collection");
        $this->assertEquals(1, $content->items[0]->_id, "The rest handler is not loading the collection");
    }

    public function testCollectionCountAndRanging()
    {
        Company::clearObjectCache();

        for ($x = 0; $x < 110; $x++) {
            $example = new User();
            $example->Forename = $x;
            $example->Surname = $x;
            $example->save();
        }

        $request = new WebRequest();
        $request->serverData["HTTP_ACCEPT"] = "application/json";
        $request->serverData["REQUEST_METHOD"] = "get";
        $request->urlPath = "/contacts/";

        $application = Application::current();
        $application->setCurrentRequest($request);
        $context = $application->context();

//        $context = new Context();
//        $context->Request = $request;

        $rest = new RestCollectionHandler(UnitTestExampleRestResource::class);
        $rest->setUrl("/contacts/");

        $response = $rest->generateResponse($request);
        $content = $response->getContent();

        $this->assertEquals(110, $content->count, "The rest collection count is invalid");
        $this->assertEquals(0, $content->range->from, "The rest collection range is invalid");
        $this->assertEquals(99, $content->range->to, "The rest collection range is invalid");
        $this->assertCount(100, $content->items, "The rest collection range is invalid");
        $this->assertEquals(42, $content->items[42]->Forename, "The rest collection range is invalid");
        $this->assertEquals(48, $content->items[48]->Forename, "The rest collection range is invalid");

        $request->server("HTTP_RANGE", "40-49");

        $response = $rest->generateResponse($request);
        $content = $response->getContent();

        $this->assertEquals(110, $content->count, "The rest collection count is invalid");
        $this->assertEquals(40, $content->range->from, "The rest collection range is invalid");
        $this->assertEquals(49, $content->range->to, "The rest collection range is invalid");
        $this->assertEquals(42, $content->items[2]->Forename, "The rest collection range is invalid");
        $this->assertEquals(48, $content->items[8]->Forename, "The rest collection range is invalid");

        $request->server("HTTP_RANGE", "");
    }

    public function testResourceCanBeUpdated()
    {
        $changes = ["Forename" => "Johnny"];

//        $context = new Context();
//        $context->SimulatedRequestBody = json_encode($changes);

//        $request = new WebRequest();
//        $request->serverData["HTTP_ACCEPT"] = "application/json";
//        $request->serverData["REQUEST_METHOD"] = "get";
//        $request->urlPath = "/contacts/";


        $request = new JsonRequest();
        $request->server("HTTP_ACCEPT", "application/json");
        $request->server("REQUEST_METHOD", "put");

        $application = Application::current();
        $application->setCurrentRequest($request);
        $context = $application->context();
        $context->simulatedRequestBody = $changes;

//        $context->Request = $request;
        $request->urlPath = "/contacts/1";

        $rest = new RestCollectionHandler(UnitTestExampleRestResource::class);
        $rest->setUrl("/contacts/");

        $response = $rest->generateResponse($request);
        $content = $response->getContent()->result;

        $example = User::findFirst();

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
        $request->server("HTTP_ACCEPT", "application/json");
        $request->server("REQUEST_METHOD", "post");

        $context->Request = $request;
        $request->UrlPath = "/contacts/";

        $rest = new RestCollectionHandler(UnitTestExampleRestResource::class);
        $rest->setUrl("/contacts/");

        Example::clearObjectCache();

        $response = $rest->generateResponse($request);
        $content = $response->getContent();

        $example = Example::findFirst();

        $this->assertEquals("Bobby", $example->Forename, "The post operation didn't update the model");
        $this->assertEquals("Smith", $example->Surname, "The post operation didn't update the model");
        $this->assertEquals("Bobby", $content->Forename);
    }

    public function testResourceCanBeDeleted()
    {
        Example::clearObjectCache();

        $example = new Example();
        $example->Forename = "Jerry";
        $example->Surname = "Maguire";
        $example->save();

        $example = new Example();
        $example->Forename = "Jolly";
        $example->Surname = "Bob";
        $example->save();

        $context = new Context();

        $request = new JsonRequest();
        $request->server("HTTP_ACCEPT", "application/json");
        $request->server("REQUEST_METHOD", "delete");

        $context->Request = $request;
        $request->UrlPath = "/contacts/" . $example->UniqueIdentifier;

        $rest = new RestCollectionHandler(UnitTestExampleRestResource::class);
        $rest->setUrl("/contacts/");

        $response = $rest->generateResponse($request);
        $content = $response->getContent();

        $this->assertCount(1, Example::find());
        $this->assertEquals("Jerry", Example::findFirst()->Forename);
        $this->assertTrue($content->result->status);

        $this->assertContains("The DELETE operation completed successfully", $content->result->message);
    }

    public function testCustomColumns()
    {
        $context = new Context();

        $request = new JsonRequest();
        $request->server("HTTP_ACCEPT", "application/json");
        $request->server("REQUEST_METHOD", "get");

        $context->Request = $request;
        $request->UrlPath = "/contacts/1";

        $rest = new RestCollectionHandler(UnitTestExampleRestResource::class);
        $rest->setUrl("/contacts/");

        $response = $rest->generateResponse($request);
        $content = $response->getContent();

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
        $request->server("HTTP_ACCEPT", "application/json");
        $request->server("REQUEST_METHOD", "get");

        $context->Request = $request;
        $request->UrlPath = "/contacts/1";

        $companyRest = new RestCollectionHandler(UnitTestCompanyRestResource::class);
        $companyRest->setUrl("/companies/");

        $rest = new RestCollectionHandler(UnitTestExampleRestResourceWithCompanyHeader::class);
        $rest->setUrl("/contacts/");

        $response = $rest->generateResponse($request);
        $content = $response->getContent();

        $this->assertTrue(isset($content->Company));
        $this->assertFalse(isset($content->Company->Balance));

        $request = new JsonRequest();
        $request->server("HTTP_ACCEPT", "application/json");
        $request->server("REQUEST_METHOD", "get");

        $context->Request = $request;
        $request->UrlPath = "/companies/1";

        $response = $companyRest->generateResponse($request);
        $company = $response->getContent();

        $this->assertEquals("Big Widgets", $company->CompanyName);
        $this->assertTrue(isset($company->Contacts));
    }

    public function testUrlsAreSet()
    {
        $request = new WebRequest();
        $request->header("HTTP_ACCEPT", "application/json");
        $request->server("REQUEST_METHOD", "get");
        $request->server("SERVER_PORT", 80);
        $request->server("HTTP_HOST", "cli");
        $request->UrlPath = "/contacts/1";

        $rest = new RestCollectionHandler(UnitTestExampleRestResource::class);

        $api = new RestApiRootHandler(UnitTestDummyResource::class,
            [
                "contacts" => $rest
            ]);

        $api->setUrl("/");

        $context = new Context();
        $context->Request = $request;

        $response = $api->generateResponse($request);
        $content = $response->getContent();

        $this->assertEquals("/contacts/1", $content->_href);
    }

    public function testCollectionIsFiltered()
    {
        $request = new WebRequest();
        $request->headerData["Accept"] = "application/json";
        $request->serverData["REQUEST_METHOD"] = "get";
        $request->serverData["SERVER_PORT"] = 80;
        $request->serverData["HTTP_HOST"] = "cli";
        $request->urlPath = "/companies/1/contacts";

        new UnitTestRestModule();
        Application::current()->initialiseModules();

//        Module::clearModules();
//        Module::registerModule(new UnitTestRestModule());
//        Module::initialiseModules();

        $context = new Context();
        $context->Request = $request;

        $response = Module::generateResponseForRequest($request);

        $content = $response->getContent();

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

    protected function initialise()
    {
        parent::initialise();

        $this->addUrlHandlers(
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
        return "UnitTestUser";
    }

    protected function getColumns()
    {
        $columns = parent::getColumns();
        $columns[] = "Forename";
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
