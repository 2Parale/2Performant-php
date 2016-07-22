<?php

namespace TPerformant\API\Model;

class Commission extends GenericEntity {
    protected $id;
    protected $userId;
    protected $actionId;
    protected $amount;
    protected $status;
    protected $affrequestId;
    protected $description;
    protected $createdAt;
    protected $updatedAt;
    protected $reason;
    protected $statsTags;
    protected $history;
    protected $currency;
    protected $workingCurrencyCode;
    protected $programId;
    protected $amountInWorkingCurrency;
    protected $program = null;
    protected $publicActionData = null;
    protected $publicClickData = null;

    protected function classMap() {
        return array_merge(parent::classMap(), [
            'program' => 'Program'
        ]);
    }
}
