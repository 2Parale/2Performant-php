<?php

namespace TPerformant\API\Filter;

class CommissionFilter extends CollectionFilter {
    protected function filterableFields() {
        return [
            'query' => 'query',
            'status' => 'status',
            'date' => 'date'
        ];
    }
}
