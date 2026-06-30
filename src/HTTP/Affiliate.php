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
use TPerformant\API\Filter\AffiliateAdvertiserPromotionFilter;
use TPerformant\API\Filter\AffiliateAdvertiserPromotionSort;

/**
 * Authentication/authorization class for affiliates
 */
class Affiliate extends User {
    /**
     * @inheritdoc
     */
    public function __construct($email = '', $password = '') {
        parent::__construct($email, $password);

        if('affiliate' != $this->getRole()) {
            throw new APIException('Authenticated user is not an affiliate');
        }
    }

    /**
     * Generate a quicklink in a certain program
     * @param  string           $url        The destination URL
     * @param  Program|string   $program    The program for which the quicklink is generated. A Program model or its unique code
     *
     * @return string           The quicklink URL
     */
    public function getQuicklink($url, $program) {
        return Api::getInstance()->getQuicklink($url, $this->getUserData(), $program);
    }

    /**
     * Get affiliate program list
     * @param  AffiliateProgramFilter   $filter (optional) Result filtering options
     * @param  AffiliateProgramSort     $sort   (optional) Result sorting options
     *
     * @return AffiliateProgram[]
     */
    public function getPrograms(AffiliateProgramFilter $filter = null, AffiliateProgramSort $sort = null) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAffiliatePrograms($this, $filter, $sort));
    }

    /**
     * Get a single affiliate program
     * @param  int|string    $id   The program's ID or slug
     *
     * @return AffiliateProgram
     */
    public function getProgram($id) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAffiliateProgram($this, $id));
    }

    /**
     * Get the affiliate request info for a certain program
     * @param  int|string    $id   The program's ID or slug
     *
     * @return Affrequest
     */
    public function getRequest($id) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAffiliateRequest($this, $id));
    }

    /**
     * Get own commissions
     * @param  AffiliateCommissionFilter    $filter (optional) Result filtering options
     * @param  AffiliateCommissionSort      $sort   (optional) Result sorting options
     *
     * @return AffiliateCommission[]|Commission[]
     */
    public function getCommissions(AffiliateCommissionFilter $filter = null, AffiliateCommissionSort $sort = null) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAffiliateCommissions($this, $filter, $sort));
    }

    /**
     * Get product feeds
     * @param  AffiliateProductFeedFilter   $filter (optional) Result filtering options
     * @param  AffiliateProductFeedSort     $sort   (optional) Result sorting options
     *
     * @return AffiliateProductFeed[]|ProductFeed[]
     */
    public function getProductFeeds(AffiliateProductFeedFilter $filter = null, AffiliateProductFeedSort $sort = null) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAffiliateProductFeeds($this, $filter, $sort));
    }

    /**
     * Get products from a product feed
     * @param  int|string               $id     Product feed's ID
     * @param  AffiliateProductFilter   $filter (optional) Result filtering options
     * @param  AffiliateProductSort     $sort   (optional) Result sorting options
     *
     * @return AffiliateProduct[]|Product[]
     */
    public function getProducts($id, AffiliateProductFilter $filter = null, AffiliateProductSort $sort = null) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAffiliateProducts($this, $id, $filter, $sort));
    }

    /**
     * Get banners
     * @param  AffiliateBannerFilter    $filter (optional) Result filtering options
     * @param  AffiliateBannerSort      $sort   (optional) Result sorting options
     *
     * @return AffiliateBanner[]|Banner[]
     */
    public function getBanners(AffiliateBannerFilter $filter = null, AffiliateBannerSort $sort = null) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAffiliateBanners($this, $filter, $sort));
    }

    /**
     * Get promotions
     * @param  AffiliateAdvertiserPromotionFilter    $filter (optional) Result filtering options
     * @param  AffiliateAdvertiserPromotionSort      $sort   (optional) Result sorting options
     *
     * @return AffiliateAdvertiserPromotion[]|AdvertiserPromotion[]
     */
    public function getPromotions(AffiliateAdvertiserPromotionFilter $filter = null, AffiliateAdvertiserPromotionSort $sort = null) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getAffiliatePromotions($this, $filter, $sort));
    }

    /**
     * Get promotions export
     * @param  AffiliateAdvertiserPromotionFilter    $filter (optional) Result filtering options
     * @param  AffiliateAdvertiserPromotionSort      $sort   (optional) Result sorting options
     *
     * @return string The promotions export CSV content
     */
    public function getPromotionsExport(AffiliateAdvertiserPromotionFilter $filter = null, AffiliateAdvertiserPromotionSort $sort = null) {
        $response = Api::getInstance()->getAffiliatePromotionsExport($this, $filter, $sort);

        $this->updateAuthTokensFromResponse($response);

        return (string) $response->getBody();
    }

    /**
     * Report lost orders by uploading a CSV file
     * @param  string $filePath Path to the CSV file containing lost orders.
     *                          The CSV must have headers: campaign_unique, order_date,
     *                          order_id, description, order_value, click_tag
     *
     * @return array The uploaded file information
     */
    public function createLostOrders($filePath) {
        $response = Api::getInstance()->createAffiliateLostOrders($this, $filePath);

        $this->updateAuthTokensFromResponse($response);

        $data = json_decode((string) $response->getBody());

        if($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new APIException(
                'Failed to decode response body: ' . json_last_error_msg(),
                $response->getStatusCode()
            );
        } 

        return $data;
    }

    /**
     * Generate Google Ads linker tracking settings
     * @param  array $trackingInfo Tracking information items. Each item must contain
     *                            a 'url' key and may optionally contain 'stats_tags'
     *
     * @return array The tracking settings for each URL
     */
    public function createGoogleAdsLinkerTrackingSettings(array $trackingInfo) {
        $response = Api::getInstance()->createAffiliateGoogleAdsLinkerTrackingSettings($this, $trackingInfo);

        $this->updateAuthTokensFromResponse($response);

        $data = json_decode((string) $response->getBody());

        if($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new APIException(
                'Failed to decode response body: ' . json_last_error_msg(),
                $response->getStatusCode()
            );
        }

        return $data;
    }
}
