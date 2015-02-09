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

namespace Rhubarb\Crown\RestApi\Authentication;

require_once __DIR__ . '/AuthenticationProvider.php';

use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\RestApi\Response\TokenAuthorisationRequiredResponse;

/**
 * An abstract authentication provider that understands how to parse the Authorization HTTP header for a token.
 *
 * Users should extend the class and implement the isTokenValid function to implement the testing of the token
 * string.
 */
abstract class TokenAuthenticationProviderBase extends AuthenticationProvider
{
    /**
     * Returns true if the token is valid.
     *
     * @param $tokenString
     * @return mixed
     */
    protected abstract function isTokenValid($tokenString);

    public function authenticate(Request $request)
    {
        if (!$request->header("Authorization")) {
            throw new ForceResponseException(new TokenAuthorisationRequiredResponse());
        }

        $authString = trim($request->header("Authorization"));

        if (stripos($authString, "token") !== 0) {
            throw new ForceResponseException(new TokenAuthorisationRequiredResponse());
        }

        if (!preg_match("/token=\"?([[:alnum:]]+)\"?/", $authString, $match)) {
            throw new ForceResponseException(new TokenAuthorisationRequiredResponse());
        }

        $token = $match[1];

        if (!$this->isTokenValid($token)) {
            throw new ForceResponseException(new TokenAuthorisationRequiredResponse());
        }

        return true;
    }
}