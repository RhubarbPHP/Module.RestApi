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

namespace Rhubarb\RestApi\Tests\Fixtures;

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\RestApi\Resources\RestCollection;
use Rhubarb\RestApi\UrlHandlers\RestHandler;

class UnitTestingRestCollection extends RestCollection
{
    protected function getItems($from, $to, RhubarbDateTime $since = null)
    {
        $item = new \stdClass();
        $item->_id = 1;

        return [[$item], 1];
    }


    public function get(RestHandler $handler = null)
    {
        return "collection";
    }
}