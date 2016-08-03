<?php

namespace TPerformant\API\HTTP;

use TPerformant\API\Api;
use TPerformant\API\Exception\APIException;
use TPerformant\API\Filter\AdvertiserProgramFilter;
use TPerformant\API\Filter\AdvertiserProgramSort;
use TPerformant\API\Filter\AdvertiserCommissionFilter;
use TPerformant\API\Filter\AdvertiserCommissionSort;

/**
 * Authentication/authorization class for advertisers
 */
class Advertiser extends User {
    /**
     * @inheritdoc
     */
    public function __construct($email = '', $password = '') {
        parent::__construct($email, $password);

        if('advertiser' != $this->getRole()) {
            throw new APIException('Provided credentials do not belong to an advertiser');
        }
    }

    /**
     * Generate a quicklink in the advertiser's own program
     * @param  string           $url        The destination URL
     * @param  Affiliate|string $affiliate  The affiliate for which the quicklink is generated. An Affiliate model or its unique code
     *
     * @return string           The quicklink URL
     */
    public function getQuicklink($url, $affiliate) {
        return Api::getInstance()->getQuicklink($url, $affiliate, $this->getMyProgram());
    }

    /**
     * Get affiliate program list
     * @param  AdvertiserProgramFilter  $filter (optional) Result filtering options
     * @param  AdvertiserProgramSort    $sort   (optional) Result sorting options
     *
     * @return AdvertiserProgram[]|Program[]
     */
    public function getPrograms(AdvertiserProgramFilter $filter = null, AdvertiserProgramSort $sort = null) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAdvertiserPrograms($this, $filter, $sort));
    }

    /**
     * Get a single affiliate program
     * @param  int|string    $id   The ID or slug of the program
     *
     * @return AdvertiserProgram|Program
     */
    public function getProgram($id) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAdvertiserProgram($this, $id));
    }

    /**
     * Get the user's own affiliate program
     *
     * @return AdvertiserProgram|Program
     */
    public function getMyProgram() {
        return $this->getProgram('default');
    }

    /**
     * Get own commission list
     * @param  AdvertiserCommissionFilter   $filter (optional) Result filtering options
     * @param  AdvertiserCommissionSort     $sort   (optional) Result sorting options
     *
     * @return AdvertiserCommision[]
     */
    public function getCommissions(AdvertiserCommissionFilter $filter = null, AdvertiserCommissionSort $sort = null) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAdvertiserCommissions($this, $filter, $sort));
    }

    /**
     * Get a single commission
     * @param  int|string       $id     The ID of the commission
     *
     * @return AdvertiserCommission
     */
    public function getCommission($id) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAdvertiserCommission($this, $id));
    }

    /**
     * Get own commission list by transaction ID
     * @param  int|string $id The transaction ID
     *
     * @return AdvertiserCommission[]
     */
    public function getTransaction($id) {
        return $this->getCommissions(
            (new AdvertiserCommissionFilter)->transactionId($id)
        );
    }

    /**
     * Create a manual commission for an affiliate
     * @param  int|string       $affiliateId The affiliate's ID
     * @param  int|float        $amount      The commission amount, in EUR
     * @param  string           $description The commission's description
     *
     * @return AdvertiserCommission
     */
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

    /**
     * Edit a commission's amount
     * @param  int|string       $id             The commission's ID
     * @param  string           $reason         A reason for the modification
     * @param  int|float        $newAmount      The new commission amount, in EUR
     * @param  string           $newDescription (optional) The new commission description, if it's the case
     *
     * @return AdvertiserCommission
     */
    public function editCommission($id, $reason, $newAmount, $newDescription = null) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->editAdvertiserCommission($this, $id, $reason, $newAmount, $newDescription));
    }

    /**
     * Mark an own commission as accepted
     * @param  int|string    $id     The commission's ID
     * @param  string        $reason (optional) The reason for accepting the commission
     *
     * @return AdvertiserCommission
     */
    public function acceptCommission($id, $reason = '') {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->acceptAdvertiserCommission($this, $id, $reason));
    }

    /**
     * Mark an own commission as rejected
     * @param  int|string    $id     The commission's ID
     * @param  string        $reason The reason for rejecting the commission
     *
     * @return AdvertiserCommission
     */
    public function rejectCommission($id, $reason) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->rejectAdvertiserCommission($this, $id, $reason));
    }
}
