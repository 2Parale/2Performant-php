<?php

namespace TPerformant\API\Filter;

class CommissionSort extends CollectionSort {
    protected function sortableFields() {
        return [
            'transactionId' => 'transaction_id',
            'date' => 'date',
            'commission' => 'commission',
            'sale' => 'sale',
            'username' => 'username',
            'updated' => 'updated'
        ];
    }
}
