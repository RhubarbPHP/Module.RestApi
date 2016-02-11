<?php

namespace Rhubarb\RestApi\Resources;

interface BinaryRestResource
{
    /**
     * @return string Name to be used for file in HTTP headers
     */
    public function getFileName();

    /**
     * @return string HTTP Content-Type header, may include charset (e.g. application/json; charset=utf-8)
     */
    public function getContentType();
}
