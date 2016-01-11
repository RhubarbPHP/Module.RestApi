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

namespace Rhubarb\RestApi\Exceptions;

/**
 * An exception which includes the response which resulted in a request failure
 *
 * @package Rhubarb\RestApi\Exceptions
 */
class RestResponseException extends RestImplementationException
{
    /** @var $response */
    public $response;

    /**
     * RestResponseException constructor.
     * @param string $message
     * @param $response
     */
    public function __construct($message, $response)
    {
        $this->response = $response;
        parent::__construct($message);
    }

}
