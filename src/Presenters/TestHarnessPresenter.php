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

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\RestApi\Clients\RestHttpRequest;
use Rhubarb\Crown\RestApi\Clients\TokenAuthenticatedRestClient;
use Rhubarb\Leaf\Presenters\Forms\Form;

class TestHarnessPresenter extends Form
{
    private $lastResponse = "";

    protected function configureView()
    {
        parent::configureView();

        $this->view->attachEventHandler("SubmitRequest", function () {
            $client = new TokenAuthenticatedRestClient(
                $this->model->ApiUrl,
                $this->model->Username,
                $this->model->Password,
                "/tokens"
            );

            $request = new RestHttpRequest($this->model->Uri, $this->model->Method, $this->model->RequestPayload);

            if ($this->model->Since) {
                $since = new RhubarbDateTime($this->model->Since);

                $request->addHeader("If-Modified-Since", $since->format("r"));
            }

            $this->lastResponse = $client->makeRequest($request);
        });
    }

    protected function createView()
    {
        return new TestHarnessView();
    }

    protected function applyModelToView()
    {
        parent::applyModelToView();

        $this->view->setResponse($this->lastResponse);
    }
}