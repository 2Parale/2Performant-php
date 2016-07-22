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

    /**
     * @inheritdoc
     */
    protected function classMap() {
        return array_merge(parent::classMap(), [
            'program' => 'Program'
        ]);
    }

    /**
     * Get products in this feed
     * @param  AffiliateProductFilter   $filter (optional) Result filtering options
     * @param  AffiliateProductSort     $sort   (optional) Result sorting options
     *
     * @return AffiliateProduct[]|Product[]
     */
    public function products(AffiliateProductFilter $filter = null, AffiliateProductSort $sort = null) {
        return $this->requester->getProducts($this->id, $filter, $sort);
    }
}
