<?php

namespace TPerformant\API\Filter;

class AffiliateAdvertiserPromotionSort extends AdvertiserPromotionSort {
    protected function sortableFields() {
        return [
            'promotionStart' => 'promotion_start',
            'promotionEnd' => 'promotion_end',
            'campaignName' => 'campaign_name',
        ];
    }
}
