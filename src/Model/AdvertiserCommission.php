<?php

namespace TPerformant\API\Model;

use TPerformant\API\HTTP\Advertiser as ApiHttpAdvertiser;

class AdvertiserCommission extends Commission {
    /**
     * @inheritdoc
     */
    public function __construct($data, ApiHttpAdvertiser $user = null) {
        parent::__construct($data, $user);
    }

    protected $transactionId;

    /**
     * Edit a commission's amount
     * @param  string           $reason         A reason for the modification
     * @param  int|float        $newAmount      The new commission amount, in EUR
     * @param  string           $newDescription (optional) The new commission description, if it's the case
     *
     * @return AdvertiserCommission
     */
    public function edit($reason, $newAmount, $newDescription = null) {
        return $this->requester->editCommission($this->id, $reason, $newAmount, $newDescription);
    }

    /**
     * Mark an own commission as accepted
     * @param  string        $reason (optional) The reason for accepting the commission
     *
     * @return AdvertiserCommission
     */
    public function accept($reason = '') {
        return $this->requester->acceptCommission($this->id, $reason);
    }

    /**
     * Mark an own commission as rejected
     * @param  string        $reason The reason for rejecting the commission
     *
     * @return AdvertiserCommission
     */
    public function reject($reason) {
        return $this->requester->rejectCommission($this->id, $reason);
    }
}
