<?php

namespace TPerformant\API\Filter;

class ProgramFilter extends CollectionFilter {
    protected function filterableFields() {
        return [
            'query' => 'query',
            'category' => 'category',
            'country' => 'country'
        ];
    }
}
