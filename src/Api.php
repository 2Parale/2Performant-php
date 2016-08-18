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

/**
 * API wrapper class
 */
class Api {
    const WRAPPER_VERSION = '1.0';
    const API_VERSION = '1';

    private $apiUrl;

    private $http = null;


    /**
     * Constructor
     * @param string $baseUrl   The base URL for API calls
     * @param array $options    Configuration options
     */
    public function __construct($baseUrl = 'https://api.2performant.com', $options = []) {
        $this->apiUrl = $baseUrl;

        $httpOptions = [
            'base_uri' => $this->apiUrl,
            'headers' => [
                'User-Agent' => 'TP-PHP-API:' . __CLASS__ . '-v' . self::WRAPPER_VERSION,
                'Content-Type' => 'application/json'
            ]
        ];

        if(isset($options['http']) && is_array($options['http']))
            $httpOptions = array_merge($httpOptions, $options['http']);

        if(!isset($httpOptions['timeout']) || 0 == $httpOptions['timeout'])
            $httpOptions['timeout'] = 5.0;

        $this->http = new HTTPClient($httpOptions);
    }

    /**
     * Authentication method
     * @param  string $email    User email address
     * @param  string $password User password
     *
     * @return ApiResponse
     */
    public function signIn($email, $password) {
        return $this->post('/users/sign_in', [
            'user' => [
                'email' => $email,
                'password' => $password
            ]
        ], 'user');
    }

    /**
     * Validate a set of credentials
     * @param  AuthInterface $auth Authentication credentials
     * @return User                The user details, if the credentials are correct
     */
    public function validateToken(AuthInterface $auth) {
        return $this->get('/users/validate_token', [], 'user', $auth);
    }

    /**
     * Get a quicklink for an affiliate in a program
     * @param  string           $url        The destination of the quicklink
     * @param  Affiliate|string $affiliate  The affiliate who owns the quicklink. Either an Affiliate object or its unique code
     * @param  Program|string   $program    The program for which the quicklink is generated. Either a Program object or its unique code
     *
     * @return string           The generated quicklink
     */
    public function getQuicklink($url, $affiliate, $program) {
        $host = preg_replace('/((?:https?\:)?\/\/.*)api((?:\.[a-zA-Z0-9\-]+)*)(\.2performant\.com)/', '$1event$2$3', $this->apiUrl);
        if(is_a($affiliate, '\\TPerformant\\API\\Model\\Affiliate'))
            $affiliate = $affiliate->getUniqueCode();
        if(is_a($program, '\\TPerformant\\API\\Model\\Program'))
            $program = $program->getUniqueCode();

        return sprintf(
            '%s/events/click?ad_type=quicklink&aff_code=%s&unique=%s&redirect_to=%s',
            $host,
            urlencode($affiliate),
            urlencode($program),
            urlencode($url)
        );
    }

    // Public methods


    // Advertiser methods

    /**
     * Get affiliate program list as an advertiser
     * @param  AuthInterface            $auth   The authentication token container
     * @param  AdvertiserProgramFilter  $filter (optional) Result filtering options
     * @param  AdvertiserProgramSort    $sort   (optional) Result sorting options
     *
     * @return ApiResponse
     */
    public function getAdvertiserPrograms(AuthInterface $auth, AdvertiserProgramFilter $filter = null, AdvertiserProgramSort $sort = null) {
        $params = [];
        if($filter)
            $params = array_merge($params, $filter->toParams());

        if($sort)
            $params = array_merge($params, $sort->toParams());

        return $this->get('/advertiser/programs', $params, 'programs', $auth);
    }

    /**
     * Get a single affiliate program as an advertiser
     * @param  AuthInterface $auth The authentication token container
     * @param  int|string    $id   The ID or slug of the program
     *
     * @return ApiResponse
     */
    public function getAdvertiserProgram(AuthInterface $auth, $id) {
        return $this->get('/advertiser/programs/'.$id, [], 'program', $auth);
    }

    /**
     * Get own commission list as an advertiser
     * @param  AuthInterface                $auth   The authentication token container
     * @param  AdvertiserCommissionFilter   $filter (optional) Result filtering options
     * @param  AdvertiserCommissionSort     $sort   (optional) Result sorting options
     *
     * @return ApiResponse
     */
    public function getAdvertiserCommissions(AuthInterface $auth, AdvertiserCommissionFilter $filter = null, AdvertiserCommissionSort $sort = null) {
        $params = [];
        if($filter)
            $params = array_merge($params, $filter->toParams());

        if($sort)
            $params = array_merge($params, $sort->toParams());

        return $this->get('/advertiser/programs/default/commissions', $params, 'commissions', $auth);
    }

    /**
     * Get a single commission as an advertiser
     * @param  AuthInterface    $auth   The authentication token container
     * @param  int|string       $id     The ID of the commission
     *
     * @return ApiResponse
     */
    public function getAdvertiserCommission(AuthInterface $auth, $id) {
        return $this->get('/advertiser/programs/default/commissions/'.$id, [], 'commission', $auth);
    }

    /**
     * Create a manual commission for an affiliate as an advertiser
     * @param  AuthInterface    $auth        The authentication token container
     * @param  int|string       $affiliateId The affiliate's ID
     * @param  int|float        $amount      The commission amount, in EUR
     * @param  string           $description The commission's description
     *
     * @return ApiResponse
     */
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

    /**
     * Edit a commission's amount as an advertiser
     * @param  AuthInterface    $auth           The authentication token container
     * @param  int|string       $id             The commission's ID
     * @param  string           $reason         A reason for the modification
     * @param  int|float        $newAmount      The new commission amount, in EUR
     * @param  string           $newDescription (optional) The new commission description, if it's the case
     *
     * @return ApiResponse
     */
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

    /**
     * Mark an own commission as accepted, as an advertiser
     * @param  AuthInterface $auth   The authentication token container
     * @param  int|string    $id     The commission's ID
     * @param  string        $reason (optional) The reason for accepting the commission
     *
     * @return ApiResponse
     */
    public function acceptAdvertiserCommission(AuthInterface $auth, $id, $reason = '') {
        $params = [
            'commission' => [
                'current_reason' => $reason
            ]
        ];

        return $this->put('/advertiser/programs/default/commissions/'.$id.'/accept', $params, 'commission', $auth);
    }

    /**
     * Mark an own commission as rejected, as an advertiser
     * @param  AuthInterface $auth   The authentication token container
     * @param  int|string    $id     The commission's ID
     * @param  string        $reason The reason for rejecting the commission
     *
     * @return ApiResponse
     */
    public function rejectAdvertiserCommission(AuthInterface $auth, $id, $reason) {
        $params = [
            'commission' => [
                'current_reason' => $reason
            ]
        ];

        return $this->put('/advertiser/programs/default/commissions/'.$id.'/reject', $params, 'commission', $auth);
    }


    // Affiliate methods

    /**
     * Get affiliate program list as an Affiliate
     * @param  AuthInterface            $auth   The authentication token container
     * @param  AffiliateProgramFilter   $filter (optional) Result filtering options
     * @param  AffiliateProgramSort     $sort   (optional) Result sorting options
     *
     * @return ApiResponse
     */
    public function getAffiliatePrograms(AuthInterface $auth, AffiliateProgramFilter $filter = null, AffiliateProgramSort $sort = null) {
        $params = [];
        if($filter)
            $params = array_merge($params, $filter->toParams());

        if($sort)
            $params = array_merge($params, $sort->toParams());

        return $this->get('/affiliate/programs', $params, 'programs', $auth);
    }

    /**
     * Get a single affiliate program as an affiliate
     * @param  AuthInterface $auth The authentication token container
     * @param  int|string    $id   The program's ID or slug
     *
     * @return ApiResponse
     */
    public function getAffiliateProgram(AuthInterface $auth, $id) {
        return $this->get('/affiliate/programs/'.$id, [], 'program', $auth);
    }

    /**
     * Get the affiliate request infor for a certain program, as an affiliate
     * @param  AuthInterface $auth The authentication token container
     * @param  int|string    $id   The program's ID or slug
     *
     * @return ApiResponse
     */
    public function getAffiliateRequest(AuthInterface $auth, $id) {
        return $this->get('/affiliate/programs/'.$id.'/me', [], 'affrequest', $auth);
    }

    /**
     * Get own commissions as an affiliate
     * @param  AuthInterface                $auth   The authentication token container
     * @param  AffiliateCommissionFilter    $filter (optional) Result filtering options
     * @param  AffiliateCommissionSort      $sort   (optional) Result sorting options
     *
     * @return ApiResponse
     */
    public function getAffiliateCommissions(AuthInterface $auth, AffiliateCommissionFilter $filter = null, AffiliateCommissionSort $sort = null) {
        $params = [];
        if($filter)
            $params = array_merge($params, $filter->toParams());

        if($sort)
            $params = array_merge($params, $sort->toParams());

        return $this->get('/affiliate/commissions', $params, 'commissions', $auth);
    }

    /**
     * Get product feeds as an affiliate
     * @param  AuthInterface                $auth   The authentication token container
     * @param  AffiliateProductFeedFilter   $filter (optional) Result filtering options
     * @param  AffiliateProductFeedSort     $sort   (optional) Result sorting options
     *
     * @return ApiResponse
     */
    public function getAffiliateProductFeeds(AuthInterface $auth, AffiliateProductFeedFilter $filter = null, AffiliateProductFeedSort $sort = null) {
        $params = [];
        if($filter)
            $params = array_merge($params, $filter->toParams());

        if($sort)
            $params = array_merge($params, $sort->toParams());

        return $this->get('/affiliate/product_feeds', $params, 'product_feeds', $auth);
    }

    /**
     * Get products from a product feed as an affiliate
     * @param  AuthInterface            $auth   The authentication token container
     * @param  int|string               $id     Product feed's ID
     * @param  AffiliateProductFilter   $filter (optional) Result filtering options
     * @param  AffiliateProductSort     $sort   (optional) Result sorting options
     *
     * @return ApiResponse
     */
    public function getAffiliateProducts(AuthInterface $auth, $id, AffiliateProductFilter $filter = null, AffiliateProductSort $sort = null) {
        $params = [];
        if($filter)
            $params = array_merge($params, $filter->toParams());

        if($sort)
            $params = array_merge($params, $sort->toParams());

        return $this->get('/affiliate/product_feeds/'.$id.'/products', $params, 'products', $auth);
    }

    /**
     * Get banners as an affiliate
     * @param  AuthInterface            $auth   The authentication token container
     * @param  AffiliateBannerFilter    $filter (optional) Result filtering options
     * @param  AffiliateBannerSort      $sort   (optional) Result sorting options
     *
     * @return ApiResponse
     */
    public function getAffiliateBanners(AuthInterface $auth, AffiliateBannerFilter $filter = null, AffiliateBannerSort $sort = null) {
        $params = [];
        if($filter)
            $params = array_merge($params, $filter->toParams());

        if($sort)
            $params = array_merge($params, $sort->toParams());

        return $this->get('/affiliate/banners', $params, 'banners', $auth);
    }


    // General request method

    /**
     * Make an API request
     * @param  string           $method     One of GET, POST, PUT, DELETE
     * @param  string           $route      The API endpoint to be requested
     * @param  array            $params     Associative array of parameters
     * @param  string           $expected   Expected object key in response hash
     * @param  AuthInterface    $auth       The authentication token container. Not needed for sign in requests
     *
     * @return ApiResponse
     */
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

    /**
     * Shorthand for API GET request
     * @param  string           $route      The API endpoint to be requested
     * @param  array            $params     Associative array of parameters
     * @param  string           $expected   Expected object key in response hash
     * @param  AuthInterface    $auth       The authentication token container. Not needed for sign in requests
     *
     * @return ApiResponse
     */
    public function get($route, $params, $expected, AuthInterface $auth = null) {
        return $this->request('GET', $route, $params, $expected, $auth);
    }

    /**
     * Shorthand for API POST request
     * @param  string           $route      The API endpoint to be requested
     * @param  array            $params     Associative array of parameters
     * @param  string           $expected   Expected object key in response hash
     * @param  AuthInterface    $auth       The authentication token container. Not needed for sign in requests
     *
     * @return ApiResponse
     */
    public function post($route, $params, $expected, AuthInterface $auth = null) {
        return $this->request('POST', $route, $params, $expected, $auth);
    }

    /**
     * Shorthand for API PUT request
     * @param  string           $route      The API endpoint to be requested
     * @param  array            $params     Associative array of parameters
     * @param  string           $expected   Expected object key in response hash
     * @param  AuthInterface    $auth       The authentication token container. Not needed for sign in requests
     *
     * @return ApiResponse
     */
    public function put($route, $params, $expected, AuthInterface $auth = null) {
        return $this->request('PUT', $route, $params, $expected, $auth);
    }

    /**
     * Shorthand for API DELETE request
     * @param  string           $route      The API endpoint to be requested
     * @param  array            $params     Associative array of parameters
     * @param  string           $expected   Expected object key in response hash
     * @param  AuthInterface    $auth       The authentication token container. Not needed for sign in requests
     *
     * @return ApiResponse
     */
    public function delete($route, $params, $expected, AuthInterface $auth = null) {
        return $this->request('DELETE', $route, $params, $expected, $auth);
    }

    /**
     * Final URL constructor for an API endpoint
     * @param  string   $route Requested API endpoint
     *
     * @return string   Full URL of the API endpoint
     */
    protected function getUrl($route) {
        // TODO extend this in case we switch to versioned URLs
        return $route . '.json';
    }


    // Singleton stuff

    /**
     * Singleton object
     * @var Api
     */
    private static $_api = null;

    /**
     * Get an instance of the Api
     * @return Api  The singleton object
     */
    public static function getInstance() {
        if(null === self::$_api) {
            self::$_api = new self();
        }

        return self::$_api;
    }

    /**
     * Construct and initialize the Api singleton object
     * @param  string   $baseUrl    The base URL for API calls
     * @param  array    $options    Configuration options
     *
     * @return Api
     */
    public static function init($baseUrl, $options = []) {
        self::$_api = new self($baseUrl, $options);
    }
}
