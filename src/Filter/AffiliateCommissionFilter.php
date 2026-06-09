<?php

namespace TPerformant\API\Filter;

class AffiliateCommissionFilter extends CommissionFilter {
    protected function filterableFields() {
        return array_merge(parent::filterableFields(), [
            'startDate' => 'start_date',
            'endDate' => 'end_date',
            'type' => 'type',
        ]);
    }
}
