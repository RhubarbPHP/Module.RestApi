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

namespace Rhubarb\Crown\RestApi\Presenters;

use Rhubarb\Leaf\Presenters\Controls\Buttons\Button;
use Rhubarb\Leaf\Presenters\Controls\DateTime\Date;
use Rhubarb\Leaf\Presenters\Controls\Selection\DropDown\DropDown;
use Rhubarb\Leaf\Presenters\Controls\Text\TextArea\TextArea;
use Rhubarb\Leaf\Presenters\Controls\Text\TextBox\TextBox;
use Rhubarb\Leaf\Views\HtmlView;

class TestHarnessView extends HtmlView
{
    private $response;

    public function setResponse($response)
    {
        $this->response = $response;
    }

    public function createPresenters()
    {
        parent::createPresenters();

        $this->addPresenters(
            new TextBox("ApiUrl"),
            new TextBox("Uri"),
            new TextBox("Username"),
            new TextBox("Password"),
            $method = new DropDown("Method"),
            new TextArea("RequestPayload", 5, 60),
            new Date("Since"),
            new Button("Submit", "Submit", function () {
                $this->RaiseEvent("SubmitRequest");
            })
        );

        $method->setSelectionItems(
            [
                ["get"],
                ["post"],
                ["put"],
                ["head"],
                ["delete"]
            ]
        );
    }

    protected function printViewContent()
    {
        parent::printViewContent();

        $this->printFieldset(
            "",
            [
                "ApiUrl",
                "Username",
                "Password",
                "Uri",
                "Method",
                "RequestPayload",
                "Since",
                "Submit"
            ]
        );

        if ($this->response) {
            print "<h2>Response</h2>";

            $pretty = json_encode($this->response, JSON_PRETTY_PRINT);

            print "<pre>" . $pretty . "</pre>";
        }
    }
}