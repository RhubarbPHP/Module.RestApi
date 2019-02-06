<?php

namespace Rhubarb\RestApi\Entities;

class SearchResponseEntity
{
    /**
     * @var SearchCriteriaEntity
     */
    public $criteria;

    /**
     * @var int
     */
    public $total;

    /**
     * @var array
     */
    public $results;

    public function __construct(SearchCriteriaEntity $criteria)
    {
        $this->criteria = $criteria;
    }
}
