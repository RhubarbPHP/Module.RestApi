<?php

namespace Rhubarb\RestApi\Exceptions;

use Throwable;

class ResourceNotFoundException extends ApiException
{
    public function __construct(string $message = "", Throwable $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}
