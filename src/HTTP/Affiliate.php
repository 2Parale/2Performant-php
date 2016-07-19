<?php

namespace TPerformant\API\HTTP;

use TPerformant\API\Api;
use TPerformant\API\Exception\APIException;
use TPerformant\API\Filter\AffiliateProgramFilter;
use TPerformant\API\Filter\AffiliateProgramSort;
use TPerformant\API\Filter\AffiliateCommissionFilter;
use TPerformant\API\Filter\AffiliateCommissionSort;
use TPerformant\API\Filter\AffiliateProductFeedFilter;
use TPerformant\API\Filter\AffiliateProductFeedSort;
use TPerformant\API\Filter\AffiliateProductFilter;
use TPerformant\API\Filter\AffiliateProductSort;
use TPerformant\API\Filter\AffiliateBannerFilter;
use TPerformant\API\Filter\AffiliateBannerSort;

class Affiliate extends User {
    public function __construct($email = '', $password = '') {
        parent::__construct($email, $password);

        if('affiliate' != $this->getRole()) {
            throw new APIException('Authenticated user is not an affiliate');
        }
    }

    public function getPrograms(AffiliateProgramFilter $filter = null, AffiliateProgramSort $sort = null) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAffiliatePrograms($this, $filter, $sort));
    }

    public function getProgram($id) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAffiliateProgram($this, $id));
    }

    public function getRequest($id) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAffiliateRequest($this, $id));
    }

    public function getCommissions(AffiliateCommissionFilter $filter = null, AffiliateCommissionSort $sort = null) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAffiliateCommissions($this, $filter, $sort));
    }

    public function getProductFeeds(AffiliateProductFeedFilter $filter = null, AffiliateProductFeedSort $sort = null) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAffiliateProductFeeds($this, $filter, $sort));
    }

    public function getProducts($id, AffiliateProductFilter $filter = null, AffiliateProductSort $sort = null) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAffiliateProducts($this, $id, $filter, $sort));
    }

    public function getBanners(AffiliateBannerFilter $filter = null, AffiliateBannerSort $sort = null) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAffiliateBanners($this, $filter, $sort));
    }
}
