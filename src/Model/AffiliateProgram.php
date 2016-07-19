<?php

namespace TPerformant\API\Model;

use TPerformant\API\Filter\AffiliateCommissionFilter;

class AffiliateProgram extends Program {
    protected $affrequest = null;

    public function getCommissions() {
        return $this->owner->getCommissions(
            (new AffiliateCommissionFilter)->query('program_name:'.$this->getName().' OR campaign_name:'.$this->getName())
        );
    }
}
