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
use Rhubarb\Crown\LoginProviders\Exceptions\LoginDisabledFailedAttemptsException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginExpiredException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException;
use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Response\BasicAuthorisationRequiredResponse;
use Rhubarb\Crown\Response\ExpiredResponse;
use Rhubarb\Crown\Response\TooManyLoginAttemptsResponse;
use Rhubarb\Stem\LoginProviders\ModelLoginProvider;

/**
 * @deprecated Just an alias of CredentialsLoginProviderAuthenticationProvider
 */
abstract class ModelLoginProviderAuthenticationProvider extends CredentialsLoginProviderAuthenticationProvider
{

}
