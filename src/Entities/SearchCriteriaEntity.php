<?php

namespace Rhubarb\RestApi\Entities;

class SearchCriteriaEntity
{
    /**
     * @var int
     */
    public $offset;
    /**
     * @var int
     */
    public $pageSize;
    /**
     * @var string
     */
    public $sort;

    public function __construct(int $offset = 0, int $pageSize = 10, string $sort = null)
    {
        $this->offset = $offset;
        $this->pageSize = $pageSize;
        $this->sort = $sort;
    }
}
