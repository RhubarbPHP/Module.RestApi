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

namespace Rhubarb\RestApi\Authentication;

require_once __DIR__ . '/AuthenticationProvider.php';

use Rhubarb\Crown\DependencyInjection\Container;
use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\Crown\LoginProviders\CredentialsLoginProviderInterface;
use Rhubarb\Crown\LoginProviders\Exceptions\CredentialsFailedException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginDisabledFailedAttemptsException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginExpiredException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException;
use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Response\BasicAuthorisationRequiredResponse;
use Rhubarb\Crown\Response\ExpiredResponse;
use Rhubarb\Crown\Response\TooManyLoginAttemptsResponse;
use Rhubarb\Stem\LoginProviders\ModelLoginProvider;

abstract class CredentialsLoginProviderAuthenticationProvider extends AuthenticationProvider
{
    protected abstract function getLoginProviderClassName();

    /**
     * @return CredentialsLoginProviderInterface
     */
    public final function getLoginProvider()
    {
        $class = $this->getLoginProviderClassName();

        return Container::singleton($class);
    }

    public function authenticate(Request $request)
    {
        if (!$request->header("Authorization")) {
            Log::debug("Authorization header missing. If using fcgi be sure to instruct Apache to include this header", "RESTAPI");
            throw new ForceResponseException(new BasicAuthorisationRequiredResponse("API"));
        }

        $authString = trim($request->header("Authorization"));

        if (stripos($authString, "basic") !== 0) {
            throw new ForceResponseException(new BasicAuthorisationRequiredResponse("API"));
        }

        $authString = substr($authString, 6);
        // Colon character support per http://www.ietf.org/rfc/rfc2617.txt
        $credentials = explode(":", base64_decode($authString), 2);

        $provider = $this->getLoginProvider();

        try {
            $provider->login($credentials[0], $credentials[1]);
            return true;
        } catch (CredentialsFailedException $er) {
            throw new ForceResponseException(new BasicAuthorisationRequiredResponse("API"));
        }
    }
}
