<?php

namespace Rhubarb\Crown\RestApi\UnitTesting;

use Rhubarb\Crown\RestApi\Clients\RestHttpRequest;
use Rhubarb\Crown\RestApi\Clients\TokenAuthenticatedRestClient;
use Rhubarb\Crown\UnitTesting\CoreTestCase;

abstract class TokenAuthenticatedRestApiClientTestCase extends CoreTestCase
{
	abstract protected function GetApiUri();

	abstract protected function GetUsername();

	abstract protected function GetPassword();

	abstract protected function GetTokensUri();

	protected function GetToken()
	{
		return false;
	}

	public function MakeApiCall( $uri, $method = "get", $payload = null )
	{
		$client = new TokenAuthenticatedRestClient(
			$this->GetApiUri(),
			$this->GetUsername(),
			$this->GetPassword(),
			$this->GetTokensUri()
		);

		$token = $this->GetToken();

		if ( $token )
		{
			$client->SetToken( $token );
		}

		$request = new RestHttpRequest( $uri, $method, $payload );
		$response = $client->MakeRequest( $request );

		return $response;
	}
} 