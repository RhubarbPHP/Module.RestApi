<?php

namespace Rhubarb\RestApi\Calls;

use Psr\Http\Message\ResponseInterface;
use Rhubarb\RestApi\RhubarbRestAPIApplication;
use Slim\Http\Environment;
use Slim\Http\Request;

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
        $app['request'] = $request;
        $this->response = $app->run(true);
    }

    public function response(): ResponseInterface
    {
        return $this->response;
    }

    public static function createRequest(string $method, string $uri, array $environment = []): Request
    {
        return Request::createFromEnvironment(Environment::mock(array_merge(
            [
                'REQUEST_METHOD' => $method,
                'REQUEST_URI' => $uri,
            ],
            $environment
        )));
    }
}
