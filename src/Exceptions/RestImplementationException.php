<?php

namespace Rhubarb\Crown\RestApi\Exceptions;

use Rhubarb\Crown\Exceptions\CoreException;

class RestImplementationException extends CoreException
{
	public function __construct( $message = "" )
	{
		parent::__construct( $message );
	}
}