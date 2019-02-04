<?php

namespace Rhubarb\RestApi\Exceptions;

class MethodNotAllowedException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Method Not Allowed', 405);
    }
}
