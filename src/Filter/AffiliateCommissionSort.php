<?php

namespace TPerformant\API\Filter;

class AffiliateCommissionSort extends CommissionSort {
    protected function sortableFields() {
        return array_merge(parent::sortableFields(), [
            'type' => 'type',
        ]);
    }
}
