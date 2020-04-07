<?php

namespace TPerformant\API\Model;

class Program extends GenericEntity {
    protected $id;
    protected $slug;
    protected $name;
    protected $mainUrl;
    protected $baseUrl;
    protected $description;
    protected $activatedAt;
    protected $userId;
    protected $uniqueCode;
    protected $status;
    protected $cookieLife;
    protected $tos;
    protected $productFeedsCount;
    protected $productsCount;
    protected $bannersCount;
    protected $approvalTime;
    protected $currency;
    protected $enableLeads;
    protected $enableSales;
    protected $defaultLeadCommissionAmount;
    protected $defaultLeadCommissionType;
    protected $defaultSaleCommissionRate;
    protected $defaultSaleCommissionType;
    protected $approvedCommissionCountRate;
    protected $approvedCommissionAmountRate;
    protected $paymentType;
    protected $balanceIndicator;
    protected $downtime;
    protected $averagePaymentTime;
    protected $logoId;
    protected $logoPath;
    protected $userLogin;
    protected $category = null;
    protected $countries = null;
    protected $ignoreIPs = null;
}
