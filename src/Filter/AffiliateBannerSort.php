<?php

namespace TPerformant\API\Filter;

class AffiliateBannerSort extends BannerSort {
    protected function sortableFields() {
        return [
            'dimensions' => 'dimensions',
            'category' => 'category',
            'friendlyType' => 'friendly_type',
            'clicks' => 'clicks',
            'actions' => 'actions',
            'conversionRate' => 'conversion_rate'
        ];
    }
}
