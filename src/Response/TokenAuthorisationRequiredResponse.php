<?php

namespace Rhubarb\Crown\RestApi\Response;

use Rhubarb\Crown\Response\NotAuthorisedResponse;

class TokenAuthorisationRequiredResponse extends NotAuthorisedResponse
{
	public function __construct($generator = null)
	{
		parent::__construct($generator);

		$this->SetHeader( "WWW-authenticate", "Token \"API\"" );
	}
}