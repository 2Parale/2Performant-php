<?php

namespace TPerformant\API\Filter;

class AffiliateProductFilter extends ProductFilter {
    protected function filterableFields() {
        return [
            'query' => 'query',
            'brand' => 'brand',
            'category' => 'category'
        ];
    }
}
