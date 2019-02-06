<?php

namespace Rhubarb\RestApi\ErrorHandlers;

use Slim\Http\Request;
use Slim\Http\Response;

class DefaultErrorHandler
{
    public function __invoke(Request $request, Response $response, \Throwable $exception = null): Response
    {
        $code = $exception->getCode();

        if (!($code > 199 && $code < 600) || !$code) {
            error_log($exception->getMessage() . ' ' . $exception->getFile() . ':' . $exception->getLine());
            $error = 'Something went wrong';
            $code = 500;
        } else {
            $error = $exception->getMessage();
        }
        return $response->withStatus($code, $error);
    }
}
