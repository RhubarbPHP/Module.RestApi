<?php

namespace Rhubarb\RestApi\Exceptions;

use Rhubarb\Crown\Exceptions\RhubarbException;

class RestImplementationException extends RhubarbException
{
    public function __construct($message = "")
    {
        parent::__construct($message);
    }
}