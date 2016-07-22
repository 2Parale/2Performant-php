<?php

namespace TPerformant\API\Filter;

class ProgramSort extends CollectionSort {
    protected function sortableFields() {
        return [
            'approvedCommissionCount' => 'approved_commission_count',
            'clickCount' => 'click_count',
            'epc' => 'epc'
        ];
    }
}
