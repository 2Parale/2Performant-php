<?php

namespace TPerformant\API;

use GuzzleHttp\Client as HTTPClient;
use TPerformant\API\Exception\ConnectionException;
use TPerformant\API\Exception\APIException;
use TPerformant\API\HTTP\ApiResponse;
use TPerformant\API\HTTP\AuthInterface;
use TPerformant\API\Filter\AdvertiserProgramFilter;
use TPerformant\API\Filter\AdvertiserProgramSort;
use TPerformant\API\Filter\AdvertiserCommissionFilter;
use TPerformant\API\Filter\AdvertiserCommissionSort;
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

class Api {
    const WRAPPER_VERSION = '1.0';

    private $apiUrl;
    private $apiVersion;

    private $http = null;


    public function __construct($baseUrl = 'https://api.2performant.com', $apiVersion = 1, $options = []) {
        $this->apiUrl = $baseUrl;
        $this->apiVersion = $apiVersion;

        $httpOptions = [
            'base_uri' => $this->apiUrl,
            'headers' => [
                'User-Agent' => 'TP-PHP-API:' . __CLASS__ . '-v' . self::WRAPPER_VERSION,
                'Content-Type' => 'application/json'
            ]
        ];

        $httpOptions = array_merge($httpOptions, $options);

        if(!isset($httpOptions['timeout']) || 0 == $httpOptions['timeout'])
            $httpOptions['timeout'] = 5.0;

        $this->http = new HTTPClient($httpOptions);
    }

    public function signIn($email, $password) {
        return $this->post('/users/sign_in', [
            'user' => [
                'email' => $email,
                'password' => $password
            ]
        ], 'user');
    }

    // Public methods
    // Advertiser methods

    public function getAdvertiserPrograms(AuthInterface $auth, AdvertiserProgramFilter $filter = null, AdvertiserProgramSort $sort = null) {
        $params = [];
        if($filter)
            $params = array_merge($params, $filter->toParams());

        if($sort)
            $params = array_merge($params, $sort->toParams());

        return $this->get('/advertiser/programs', $params, 'programs', $auth);
    }

    public function getAdvertiserProgram(AuthInterface $auth, $id) {
        return $this->get('/advertiser/programs/'.$id, [], 'program', $auth);
    }

    public function getAdvertiserCommissions(AuthInterface $auth, AdvertiserCommissionFilter $filter = null, AdvertiserCommissionSort $sort = null) {
        $params = [];
        if($filter)
            $params = array_merge($params, $filter->toParams());

        if($sort)
            $params = array_merge($params, $sort->toParams());

        return $this->get('/advertiser/programs/default/commissions', $params, 'commissions', $auth);
    }

    public function getAdvertiserCommission(AuthInterface $auth, $id) {
        return $this->get('/advertiser/programs/default/commissions/'.$id, [], 'commission', $auth);
    }

    public function createAdvertiserCommission(AuthInterface $auth, $affiliateId, $amount, $description) {
        $params = [
            'commission' => [
                'user_id' => $affiliateId,
                'amount' => $amount,
                'description' => $description
            ]
        ];

        return $this->post('/advertiser/programs/default/commissions', $params, 'commission', $auth);
    }

    public function editAdvertiserCommission(AuthInterface $auth, $id, $reason, $newAmount, $newDescription = null) {
        $params = [
            'commission' => [
                'current_reason' => $reason,
                'amount' => $newAmount
            ]
        ];
        if($newDescription) {
            $params['commission']['description'] = $newDescription;
        }

        return $this->put('/advertiser/programs/default/commissions/'.$id, $params, 'commission', $auth);
    }

    public function acceptAdvertiserCommission(AuthInterface $auth, $id, $reason = '') {
        $params = [
            'commission' => [
                'current_reason' => $reason
            ]
        ];

        return $this->put('/advertiser/programs/default/commissions/'.$id.'/accept', $params, 'commission', $auth);
    }

    public function rejectAdvertiserCommission(AuthInterface $auth, $id, $reason) {
        $params = [
            'commission' => [
                'current_reason' => $reason
            ]
        ];

        return $this->put('/advertiser/programs/default/commissions/'.$id.'/reject', $params, 'commission', $auth);
    }


    // Affiliate methods

    public function getAffiliatePrograms(AuthInterface $auth, AffiliateProgramFilter $filter = null, AffiliateProgramSort $sort = null) {
        $params = [];
        if($filter)
            $params = array_merge($params, $filter->toParams());

        if($sort)
            $params = array_merge($params, $sort->toParams());

        return $this->get('/affiliate/programs', $params, 'programs', $auth);
    }

    public function getAffiliateProgram(AuthInterface $auth, $id) {
        return $this->get('/affiliate/programs/'.$id, [], 'program', $auth);
    }

    public function getAffiliateRequest(AuthInterface $auth, $id) {
        return $this->get('/affiliate/programs/'.$id.'/me', [], 'affrequest', $auth);
    }

    public function getAffiliateCommissions(AuthInterface $auth, AffiliateCommissionFilter $filter = null, AffiliateCommissionSort $sort = null) {
        $params = [];
        if($filter)
            $params = array_merge($params, $filter->toParams());

        if($sort)
            $params = array_merge($params, $sort->toParams());

        return $this->get('/affiliate/commissions', $params, 'commissions', $auth);
    }

    public function getAffiliateProductFeeds(AuthInterface $auth, AffiliateProductFeedFilter $filter = null, AffiliateProductFeedSort $sort = null) {
        $params = [];
        if($filter)
            $params = array_merge($params, $filter->toParams());

        if($sort)
            $params = array_merge($params, $sort->toParams());

        return $this->get('/affiliate/product_feeds', $params, 'product_feeds', $auth);
    }

    public function getAffiliateProducts(AuthInterface $auth, $id, AffiliateProductFilter $filter = null, AffiliateProductSort $sort = null) {
        $params = [];
        if($filter)
            $params = array_merge($params, $filter->toParams());

        if($sort)
            $params = array_merge($params, $sort->toParams());

        return $this->get('/affiliate/product_feeds/'.$id.'/products', $params, 'products', $auth);
    }

    public function getAffiliateBanners(AuthInterface $auth, AffiliateBannerFilter $filter = null, AffiliateBannerSort $sort = null) {
        $params = [];
        if($filter)
            $params = array_merge($params, $filter->toParams());

        if($sort)
            $params = array_merge($params, $sort->toParams());

        return $this->get('/affiliate/banners', $params, 'banners', $auth);
    }


    // General request method

    public function request($method, $route, $params, $expected, AuthInterface $auth = null) {
        $url = $this->getUrl($route);

        $requestOptions = [];

        // authentication headers
        if($auth) {
            $requestOptions['headers'] = [
                'access-token' => $auth->getAccessToken(),
                'client' => $auth->getClientToken(),
                'uid' => $auth->getUid()
            ];
        }

        // request body
        if('GET' === $method) {
            $requestOptions['query'] = $params;
        } else {
            $requestOptions['json'] = $params;
        }

        try {
            $response = $this->http->request($method, $url, $requestOptions);
        } catch(\GuzzleHttp\Exception\ConnectException $e) {
            throw new ConnectionException($e->getMessage(), 0, $e);
        } catch(\GuzzleHttp\Exception\BadResponseException $e) {
            $message = 'API responded with an error: '.$e->getCode();

            $response = json_decode($e->getResponse()->getBody());
            if($response && $response->errors) {
                $message = [];
                foreach ($response->errors as $error) {
                    $message[] = $error->title;
                }

                $message = 'API responded with code ' . $e->getResponse()->getStatusCode() . ' and the following error(s): ' . implode(', ', $message);
            }
            throw new APIException($message, $e->getCode(), $e);
        }

        return new ApiResponse($response, $expected, $auth);
    }

    public function get($route, $params, $expected, AuthInterface $auth = null) {
        return $this->request('GET', $route, $params, $expected, $auth);
    }

    public function post($route, $params, $expected, AuthInterface $auth = null) {
        return $this->request('POST', $route, $params, $expected, $auth);
    }

    public function put($route, $params, $expected, AuthInterface $auth = null) {
        return $this->request('PUT', $route, $params, $expected, $auth);
    }

    public function delete($route, $params, $expected, AuthInterface $auth = null) {
        return $this->request('DELETE', $route, $params, $expected, $auth);
    }

    protected function getUrl($route) {
        // TODO extend this in case we switch to versioned URLs
        return $route . '.json';
    }


    // Singleton stuff

    private static $_api = null;

    public static function getInstance() {
        if(null === self::$_api) {
            self::$_api = new self();
        }

        return self::$_api;
    }

    public static function init($baseUrl, $apiVersion = 1, $options = []) {
        self::$_api = new self($baseUrl, $apiVersion, $options);
    }
}
