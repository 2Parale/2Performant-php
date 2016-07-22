<?php

namespace TPerformant\API\Filter;

class AffiliateProductFeedFilter extends ProductFeedFilter {
    protected function filterableFields() {
        return [
            'programId' => 'program_id'
        ];
    }
}
