<?php

namespace Rhubarb\RestApi\Resources;

class ListResource
{
    public $count;

    public $range;

    public function __construct()
    {
        $this->range = new Range();
    }

}