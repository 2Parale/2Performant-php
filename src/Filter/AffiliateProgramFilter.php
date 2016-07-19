<?php

namespace TPerformant\API\Filter;

class AffiliateProgramFilter extends ProgramFilter {
    protected function filterableFields() {
        return array_merge(parent::filterableFields(), [
            'relation' => 'relation'
        ]);
    }
}
