<?php

namespace TPerformant\API\Model;

class Advertiser extends User {
    public function __construct($email = '', $password = '') {
        parent::__construct($email, $password);

        if('advertiser' != $this->role) {
            throw new TPExceptionLogic('Provided credentials do not belong to an advertiser');
        }
    }

    public function getCommissions() {
        //
    }
}
