<?php

namespace Rhubarb\Crown\RestApi\Clients;
use Rhubarb\Crown\Integration\Http\HttpClient;
use Rhubarb\Crown\Integration\Http\HttpRequest;

/**
 * A very simple REST client using Curl.
 *
 * Note it is rare you would use this class directly. Most often you will need a client that
 * supports authentication in some way.
 */
class RestClient
{
	protected $_apiUrl;

	public function __construct( $apiUrl )
	{
		$this->_apiUrl = rtrim( $apiUrl, "/" );
	}

	protected function ApplyAuthenticationDetailsToRequest( HttpRequest $request )
	{

	}

	public function MakeRequest(RestHttpRequest $request)
	{
		$request->SetApiUrl( $this->_apiUrl );
		$request->AddHeader( "Accept", "application/xml" );

		$this->ApplyAuthenticationDetailsToRequest( $request );

		$httpClient = HttpClient::getDefaultHttpClient();
		$response = $httpClient->getResponse( $request );

		return json_decode( $response->GetResponseBody() );
	}
} 