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
     * @param  string                   $reason         A reason for the modification
     * @param  int|float|Array|Object   $newAmount      The new commission amount. If numeric, the currency will be considered EUR. Arrays and objects must define `amount` and `currencyCode.`
     * @param  string                   $newDescription (optional) The new commission description, if it's the case
     *
     * @return AdvertiserCommission
     */
    public function edit($reason, $newAmount, $newDescription = null) {
        $currency = null;
        $amount = null;

        if(is_numeric($newAmount)) {
            $amount = $newAmount;
        } else {
            if(is_array($newAmount)) {
                if(!isset($newAmount['amount'])) {
                    throw new TPException('If second argument of AdvertiserCommission::edit() is an associative array, then the "amount" must be provided');
                }
                $amount = $newAmount['amount'];

                if(isset($newAmount['currencyCode'])) {
                    $currency = $newAmount['currencyCode'];
                }
            } else if(is_object($newAmount)) {
                if(!property_exists ($newAmount, 'amount')) {
                    throw new TPException('If second argument of AdvertiserCommission::edit() is an object, then the "amount" must be a public property');
                }
                $amount = $newAmount->amount;

                if(property_exists($newAmount, 'currencyCode')) {
                    $currency = $newAmount->currencyCode;
                }
            } else {
                throw new TPException('Second argument of AdvertiserCommission::edit() must be a number, an associative array or an object');
            }
        }

        return $this->requester->editCommission($this->id, $reason, array(
            'amount' => $amount,
            'currencyCode' => $currency
        ), $newDescription);
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
