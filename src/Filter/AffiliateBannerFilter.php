<?php

namespace TPerformant\API\Filter;

class AffiliateBannerFilter extends BannerFilter {
    protected function filterableFields() {
        return [
            'query' => 'query',
            'dimensions' => 'dimensions',
            'category' => 'category',
            'friendlyType' => 'friendly_type'
        ];
    }
}
