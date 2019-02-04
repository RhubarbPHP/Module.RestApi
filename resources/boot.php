<?php

http_response_code(500);

$apiClass = getenv('API_CLASS');

if (!$apiClass) {
    error_log('An `API_CLASS` environment variable is required');
    return;
}

try {
    /** @var \Rhubarb\RestApi\RhubarbRestAPIApplication $app */
    $app = new $apiClass();
    if (!$app instanceof \Rhubarb\RestApi\RhubarbRestAPIApplication) {
        error_log('API Application must be an instance of `RhubarbAPIApplication`');
        return;
    }
    $app
        ->initialise()
        ->run();
} catch (\Error $error) {
    error_log($error->getMessage() . ' ' . $error->getFile() . ':' . $error->getLine());
    http_response_code(500);
}

