<?php

namespace Rhubarb\RestApi\Calls;

use Psr\Http\Message\ResponseInterface;
use Rhubarb\RestApi\RhubarbRestAPIApplication;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\ServerRequestCreatorFactory;

/**
 * Class FakeCall
 * @package Rhubarb\RestApi\Calls
 * @deprecated For use in tests only
 */
class FakeCall
{
    /** @var ResponseInterface */
    private $response;

    public function __construct(RhubarbRestAPIApplication $application, Request $request)
    {
        $app = $application->initialise();
        $this->response = $app->handle($request);
    }

    public function response(): ResponseInterface
    {
        return $this->response;
    }

    public static function createRequest(string $method, string $uri): Request
    {
       
        $request = (new ServerRequestCreatorFactory())->create()->createServerRequestFromGlobals();

        $request->withAttribute("method", $method);
        $request->withAttribute("uri", $uri);
        return $request;

    }
}
