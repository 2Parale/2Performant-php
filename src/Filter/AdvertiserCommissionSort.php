<?php

namespace TPerformant\API\Filter;

class AdvertiserCommissionSort extends CommissionSort {
    protected function sortableFields() {
        return array_merge(parent::sortableFields(), [
            'type' => 'type',
        ]);
    }
}
