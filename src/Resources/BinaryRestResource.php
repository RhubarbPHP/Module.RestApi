<?php

namespace Rhubarb\RestApi\Resources;

interface BinaryRestResource
{
    /**
     * @return string Name to be used for file in HTTP headers
     */
    public function getFileName();
}
