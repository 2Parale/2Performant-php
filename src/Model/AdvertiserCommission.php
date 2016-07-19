<?php

namespace TPerformant\API\Model;

class AdvertiserCommission extends Commission {
    protected $transactionId;

    public function edit($reason, $newAmount, $newDescription = null) {
        return $this->requester->editCommission($this->id, $reason, $newAmount, $newDescription);
    }

    public function accept($reason = '') {
        return $this->requester->acceptCommission($this->id, $reason);
    }

    public function reject($reason) {
        return $this->requester->rejectCommission($this->id, $reason);
    }
}
