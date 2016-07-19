<?php

namespace TPerformant\API\HTTP;

use TPerformant\API\Api;
use TPerformant\API\Exception\APIException;
use TPerformant\API\Filter\AdvertiserProgramFilter;
use TPerformant\API\Filter\AdvertiserProgramSort;
use TPerformant\API\Filter\AdvertiserCommissionFilter;
use TPerformant\API\Filter\AdvertiserCommissionSort;

class Advertiser extends User {
    public function __construct($email = '', $password = '') {
        parent::__construct($email, $password);

        if('advertiser' != $this->getRole()) {
            throw new APIException('Provided credentials do not belong to an advertiser');
        }
    }

    public function getPrograms(AdvertiserProgramFilter $filter = null, AdvertiserProgramSort $sort = null) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAdvertiserPrograms($this, $filter, $sort));
    }

    public function getProgram($id) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAdvertiserProgram($this, $id));
    }

    public function getMyProgram() {
        return $this->getProgram('default');
    }

    public function getCommissions(AdvertiserCommissionFilter $filter = null, AdvertiserCommissionSort $sort = null) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAdvertiserCommissions($this, $filter, $sort));
    }

    public function getCommission($id) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAdvertiserCommission($this, $id));
    }

    public function getTransaction($id) {
        return $this->getCommissions(
            (new AdvertiserCommissionFilter)->transactionId($id)
        );
    }

    public function createCommission($affiliate, $amount, $description) {
        if(!is_numeric($affiliate)) {
            if(is_object($affiliate) && is_subclass_of($affiliate, __NAMESPACE__ . '\\Affiliate')) {
                $affiliate = $affiliate->getId();
            } else {
                throw new TPException('First parameter must be an affiliate ID or an Affiliate object');
            }
        }

        return $this->updateAuthTokensAndReturn(Api::getInstance()->createAdvertiserCommission($this, $affiliate, $amount, $description));
    }

    public function editCommission($id, $reason, $newAmount, $newDescription = null) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->editAdvertiserCommission($this, $id, $reason, $newAmount, $newDescription));
    }

    public function acceptCommission($id, $reason = '') {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->acceptAdvertiserCommission($this, $id, $reason));
    }

    public function rejectCommission($id, $reason) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->rejectAdvertiserCommission($this, $id, $reason));
    }
}
