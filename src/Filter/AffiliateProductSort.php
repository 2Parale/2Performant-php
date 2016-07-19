<?php

namespace TPerformant\API\Filter;

class AffiliateProductSort extends ProductSort {
    protected function sortableFields() {
        return [
            'price' => 'price',
            'actions' => 'actions',
            'clicks' => 'clicks'
        ];
    }
}
