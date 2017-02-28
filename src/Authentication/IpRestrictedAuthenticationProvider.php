<?php
/**
 * Copyright (c) 2017 RhubarbPHP.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Rhubarb\RestApi\Authentication;

use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Response\NotAuthorisedResponse;

class IpRestrictedAuthenticationProvider extends AuthenticationProvider
{
    private $ipList = [];

    public function __construct($ipList = [])
    {
        $this->ipList = $ipList;
    }

    public function authenticate(Request $request)
    {
        if (!($request instanceof WebRequest)){
            throw new ForceResponseException(new NotAuthorisedResponse($this));
        }

        /**
         * @var WebRequest $request
         */
        $ip = $request->server("REMOTE_ADDR");

        if (!in_array($ip, $this->ipList)) {
            throw new ForceResponseException(new NotAuthorisedResponse($this));
        }

        return true;
    }
}