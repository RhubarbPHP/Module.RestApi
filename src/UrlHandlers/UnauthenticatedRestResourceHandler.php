<?php

namespace Rhubarb\Crown\RestApi\UrlHandlers;

class UnauthenticatedRestResourceHandler extends RestResourceHandler
{
	/**
	 * Returns false to stop the API from checking the default AuthenticationProvider for this handler.
	 *
	 * @return bool|null
	 */
	protected function CreateAuthenticationProvider()
	{
		return false;
	}

} 