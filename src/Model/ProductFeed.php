<?php

namespace TPerformant\API\Model;

use TPerformant\API\Filter\AffiliateProductFilter;
use TPerformant\API\Filter\AffiliateProductSort;

class ProductFeed extends GenericEntity {
    protected $id;
    protected $updatedAt;
    protected $help;
    protected $productsCount;
    protected $name;
    protected $program;

    protected function classMap() {
        return array_merge(parent::classMap(), [
            'program' => 'Program'
        ]);
    }

    public function products(AffiliateProductFilter $filter = null, AffiliateProductSort $sort = null) {
        return $this->requester->getProducts($this->id, $filter, $sort);
    }
}
