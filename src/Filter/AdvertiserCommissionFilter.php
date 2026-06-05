<?php

namespace TPerformant\API\Filter;

class AdvertiserCommissionFilter extends CommissionFilter {
    protected function filterableFields() {
        return array_merge(parent::filterableFields(), [
            'transactionId' => 'transaction_id',
            'startDate' => 'start_date',
            'endDate' => 'end_date',
            'type' => 'type',
        ]);
    }
}
