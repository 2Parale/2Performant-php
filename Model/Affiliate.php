<?php

namespace TPerformant\API\Model;

class Affiliate extends User {
    public function __construct($email = '', $password = '') {
        parent::__construct($email, $password);

        if('affiliate' !== $this->getRole()) {
            throw new TPLogicException('Authenticated user is not an affiliate');
        }
    }

    public function getMyPrograms() {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAffiliateProgramRequests($this));
    }

    public function getCommissions() {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAffiliateCommissions($this));
    }
}
