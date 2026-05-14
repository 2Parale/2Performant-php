<?php

namespace TPerformant\API\Model;

class Sale extends GenericEntity {
    protected $id;
    protected $amount;
    protected $currencyCode;
    protected $amountInWorkingCurrency;
    protected $workingCurrencyCode;
}
