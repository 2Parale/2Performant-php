<?php

namespace TPerformant\API\Filter;

class AffiliateAdvertiserPromotionFilter extends AdvertiserPromotionFilter {
    protected function filterableFields() {
        return [
            'affrequestStatus' => 'affrequest_status'
        ];
    }
}
