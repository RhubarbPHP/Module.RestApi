<?php

namespace Rhubarb\Crown\RestApi;

use Rhubarb\Crown\Module;

/** 
 * A module to handle RESTful APIs
 *
 * @package Rhubarb\Crown\Api
 * @author      acuthbert
 * @copyright   2013 GCD Technologies Ltd.
 */
class RestApiModule extends Module
{
	public function __construct()
	{
		parent::__construct();

		$this->namespace = __NAMESPACE__;
	}
}