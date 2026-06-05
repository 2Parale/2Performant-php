<?php

namespace TPerformant\API;

use GuzzleHttp\Client as HTTPClient;
use TPerformant\API\Exception\ConnectionException;
use TPerformant\API\Exception\TPException;
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
use TPerformant\API\Filter\AffiliateAdvertiserPromotionFilter;
use TPerformant\API\Filter\AffiliateAdvertiserPromotionSort;

/**
 * API wrapper class
 */
class Api {
    const WRAPPER_VERSION = '1.0';
    const API_VERSION = '1.0.1';

    private $apiUrl;

    private $http = null;


    /**
     * Constructor
     * @param string $baseUrl   The base URL for API calls
     * @param array $options    Configuration options
     */
    public function __construct($baseUrl = 'https://api.2performant.com', $options = []) {
       
        $scheme = parse_url($baseUrl, PHP_URL_SCHEME);
        $scheme = is_string($scheme) ? strtolower($scheme) : '';
        if ($scheme !== 'https') {
            throw new \InvalidArgumentException('Base URL must use the HTTPS scheme');
        }

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
        ], 'user', null, true);
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
        $this->validateId($id, 'getAdvertiserProgram');
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
        $this->validateId($id, 'getAdvertiserCommission');
        return $this->get('/advertiser/programs/default/commissions/'.$id, [], 'commission', $auth);
    }

    /**
     * Create a manual commission for an affiliate as an advertiser
     * @param  AuthInterface    $auth        The authentication token container
     * @param  int|string       $affiliateId The affiliate's ID
     * @param  int|float        $amount      The commission amount, in EUR
     * @param  string           $description The commission's description
     * @param  string           $currencyCode (optional) The commission's currency code
     *
     * @return ApiResponse
     */
    public function createAdvertiserCommission(AuthInterface $auth, $affiliateId, $amount, $description, $currencyCode = null) {
        $params = [
            'commission' => [
                'user_id' => $affiliateId,
                'amount' => $amount,
                'description' => $description
            ]
        ];

        if($currencyCode && is_string($currencyCode) && strlen(trim($currencyCode)) === 3) {
            $params['commission']['currency_code'] = trim($currencyCode);
        }

        return $this->post('/advertiser/programs/default/commissions', $params, 'commission', $auth);
    }

    /**
     * Edit a commission's amount as an advertiser
     * @param  AuthInterface    $auth           The authentication token container
     * @param  int|string       $id             The commission's ID
     * @param  string           $reason         A reason for the modification
     * @param  int|float|Array  $newAmount      The new commission amount. If numeric, currency is considered to be EUR. If array, it must have `amount` and `currencyCode`
     * @param  string           $newDescription (optional) The new commission description, if it's the case
     *
     * @return ApiResponse
     */
    public function editAdvertiserCommission(AuthInterface $auth, $id, $reason, $newAmount, $newDescription = null) {
        if(is_numeric($newAmount)) {
            $newAmount = [
                'amount' => $newAmount,
                'currencyCode' => null
            ];
        } else {
            if(!is_array($newAmount)) {
                throw new TPException('Fourth argument of Api::editAdvertiserCommission() must be a number or an array');
            }
        }

        $params = [
            'commission' => [
                'reason' => $reason,
                'amount' => $newAmount['amount'],
                'currency_code' => $newAmount['currencyCode'] ?: 'EUR'
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

    /**
     * Update a sale commission's amount as an advertiser
     * @param  AuthInterface    $auth          The authentication token container
     * @param  int|string       $id            The commission's ID
     * @param  string           $amount        The new amount of the sale
     * @param  string           $currencyCode  The currency code of the sale
     * @param  string           $reason        The reason why changes are made on the sale
     *
     * @return ApiResponse
     */
    public function updateAdvertiserSaleCommission(AuthInterface $auth, $id, $amount, $currencyCode, $reason) {
        $params = [
            'sale' => [
                'amount' => $amount,
                'currency_code' => $currencyCode,
                'reason' => $reason
            ]
        ];

        return $this->put('/advertiser/programs/default/commissions/'.$id.'/update_sale', $params, 'sale', $auth);
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

    /**
     * Get promotions as an affiliate
     * @param  AuthInterface            $auth   The authentication token container
     * @param  AffiliateAdvertiserPromotionFilter    $filter (optional) Result filtering options
     * @param  AffiliateAdvertiserPromotionSort      $sort   (optional) Result sorting options
     *
     * @return ApiResponse
     */
    public function getAffiliatePromotions(AuthInterface $auth, AffiliateAdvertiserPromotionFilter $filter = null, AffiliateAdvertiserPromotionSort $sort = null) {
        $params = [];
        if($filter)
            $params = array_merge($params, $filter->toParams());

        if($sort)
            $params = array_merge($params, $sort->toParams());

        return $this->get('/affiliate/advertiser_promotions', $params, 'advertiser_promotions', $auth);
    }


    /**
     * Export promotions as CSV as an affiliate
     * Returns CSV with promotions published by advertisers. Respects
     * the same filter params as getAffiliatePromotions but ignores pagination.
     * @param  AuthInterface                         $auth   The authentication token container
     * @param  AffiliateAdvertiserPromotionFilter    $filter (optional) Result filtering options
     * @param  AffiliateAdvertiserPromotionSort      $sort   (optional) Result sorting options
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getAffiliatePromotionsExport(AuthInterface $auth, AffiliateAdvertiserPromotionFilter $filter = null, AffiliateAdvertiserPromotionSort $sort = null) {
        $params = [];
        if($filter) {
            $params = array_merge($params, $filter->toParams());
            unset($params['page'], $params['perpage']);
        }
        if($sort)
            $params = array_merge($params, $sort->toParams());

        return $this->requestRaw('GET', '/affiliate/advertiser_promotions/export', $params, $auth);
    }

    /**
     * Report lost orders as an affiliate by uploading a CSV file
     * @param  AuthInterface $auth      The authentication token container
     * @param  string        $filePath  Path to the CSV file containing lost orders.
     *                                  The CSV must have headers: campaign_unique, order_date,
     *                                  order_id, description, order_value, click_tag
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function createAffiliateLostOrders(AuthInterface $auth, $filePath) {
        $this->validateCsvFile($filePath, [
            'campaign_unique', 'order_date', 'order_id',
            'description', 'order_value', 'click_tag'
        ]);

        $file = fopen($filePath, 'r');

        $multipart = [
            [
                'name' => 'source_file',
                'contents' => $file,
                'filename' => basename($filePath)
            ]
        ];

        try{
            return $this->requestMultipart('POST', '/affiliate/lost_orders', $multipart, $auth);
        }
        finally {
            if(is_resource($file)) {
                fclose($file);
            }
        }
    }

    /**
     * Generate Google Ads linker tracking settings as an affiliate
     * @param  AuthInterface $auth          The authentication token container
     * @param  array         $trackingInfo  Tracking information items. Each item must contain
     *                                      a 'url' key and may optionally contain 'stats_tags'
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function createAffiliateGoogleAdsLinkerTrackingSettings(AuthInterface $auth, array $trackingInfo) {
        $this->validateTrackingInfo($trackingInfo);

        $params = [
            'tracking_info' => $trackingInfo
        ];

        return $this->requestRaw('POST', '/affiliate/google_ads_linker/tracking_settings', $params, $auth);
    }


    // General request method

    /**
     * Make an API request
     * @param  string           $method     One of GET, POST, PUT, DELETE
     * @param  string           $route      The API endpoint to be requested
     * @param  array            $params     Associative array of parameters
     * @param  string           $expected   Expected object key in response hash
     * @param  AuthInterface    $auth       The authentication token container. Not needed for sign in requests
     * @param  bool             $sensitive  Whether the request contains sensitive information that should not be included in debug logs
     *
     * @return ApiResponse
     */
    public function request($method, $route, $params, $expected, AuthInterface $auth = null, $sensitive = false) {
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

        $response = $this->executeRequest($method, $url, $requestOptions, $sensitive);

        return new ApiResponse($response, $expected, $auth);
    }

    /**
     * Make a multipart/form-data API request (file upload)
     *
     * Unlike request(), this method does not go through ApiResponse because
     * file upload endpoints may return non-standard response formats (e.g. a
     * plain JSON array instead of a keyed object).
     *
     * @param  string           $method     HTTP method (POST, PUT, etc.)
     * @param  string           $route      The API endpoint to be requested
     * @param  array            $multipart  Guzzle-compatible multipart form data
     * @param  AuthInterface    $auth       The authentication token container
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function requestMultipart($method, $route, array $multipart, AuthInterface $auth = null) {
        $url = $this->getUrl($route);

        $body = new \GuzzleHttp\Psr7\MultipartStream($multipart);

        $requestOptions = [
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary=' . $body->getBoundary()
            ],
            'body' => $body
        ];

        if($auth) {
            $requestOptions['headers']['access-token'] = $auth->getAccessToken();
            $requestOptions['headers']['client'] = $auth->getClientToken();
            $requestOptions['headers']['uid'] = $auth->getUid();
        }

        $response = $this->executeRequest($method, $url, $requestOptions);

        $this->throwOnErrorResponse($response);

        return $response;
    }

    /**
     * Make a raw API request that returns non-JSON responses (e.g. CSV exports)
     *
     * Unlike request(), this method does not wrap the response in ApiResponse,
     * returning the raw PSR-7 response instead.
     *
     * @param  string           $method     HTTP method (GET, POST, etc.)
     * @param  string           $route      The API endpoint to be requested
     * @param  array            $params     Associative array of parameters
     * @param  AuthInterface    $auth       The authentication token container
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function requestRaw($method, $route, array $params = [], AuthInterface $auth = null) {
        $url = $this->getUrl($route);

        $requestOptions = [];

        if($auth) {
            $requestOptions['headers'] = [
                'access-token' => $auth->getAccessToken(),
                'client' => $auth->getClientToken(),
                'uid' => $auth->getUid()
            ];
        }

        if('GET' === $method) {
            $requestOptions['query'] = $params;
        } else {
            $requestOptions['json'] = $params;
        }

        $response = $this->executeRequest($method, $url, $requestOptions);

        $this->throwOnErrorResponse($response);

        return $response;
    }

    /**
     * Execute a request and handle exceptions
     * @param  string           $method     HTTP method (GET, POST, etc.)
     * @param  string           $url        The URL to be requested
     * @param  array            $requestOptions     Request options
     * @param  bool             $sensitive  Whether the request contains sensitive information that should not be included in debug logs
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \TPerformant\API\Exception\ServerException
     * @throws \TPerformant\API\Exception\ConnectionException
     * @throws \TPerformant\API\Exception\TransferException
     */
    private function executeRequest($method, $url, $requestOptions, $sensitive = false) {
        if($sensitive) {
            $requestOptions['debug'] = false;
        }
        try {
            $response = $this->http->request($method, $url, $requestOptions);
        } catch(\GuzzleHttp\Exception\ServerException $e) {
            throw \TPerformant\API\Exception\ServerException::create($e);
        } catch(\GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
        } catch(\GuzzleHttp\Exception\ConnectException $e) {
            throw \TPerformant\API\Exception\ConnectionException::create($e);
        } catch(\GuzzleHttp\Exception\TransferException $e) {
            throw new \TPerformant\API\Exception\TransferException($e->getMessage(), $e->getCode());
        }

        return $response;
    }

    /**
     * Throw an exception if the response is an error
     * @param  \Psr\Http\Message\ResponseInterface $response The response to check
     *
     * @return void
     *
     * @throws \TPerformant\API\Exception\APIException
     */
    private function throwOnErrorResponse($response) {
        $statusCode = $response->getStatusCode();

        if($statusCode >= 400) {
            $data = json_decode((string) $response->getBody(), true);
            $errors = is_array($data) && isset($data['errors']) ? $data['errors'] : [$response->getReasonPhrase()];

            $messages = [];
            foreach($errors as $error) {
                if(is_string($error)) {
                    $messages[] = $error;
                } elseif(is_array($error)) {
                    $m = isset($error['title']) ? $error['title'] : (isset($error['error']) ? $error['error'] : json_encode($error));
                    if(isset($error['detail'])) {
                        $m .= ' - ' . $error['detail'];
                    } elseif(isset($error['details'])) {
                        $m .= ' - ' . $error['details'];
                    }
                    $messages[] = $m;
                }
            }

            throw new APIException(
                'API responded with code ' . $statusCode . ': ' . implode(', ', $messages),
                $statusCode
            );
        }
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
     * @param  bool             $sensitive  Whether the request contains sensitive information that should not be included in debug logs
     *
     * @return ApiResponse
     */
    public function post($route, $params, $expected, AuthInterface $auth = null, $sensitive = false) {
        return $this->request('POST', $route, $params, $expected, $auth, $sensitive);
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

    // validations

    /**
     * Validate a CSV file
     * @param  string $filePath Path to the CSV file
     * @param  array $requiredHeaders Required headers
     * @return void
     */
    private function validateCsvFile($filePath, array $requiredHeaders) {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException(
                sprintf('File not found or not readable: %s', $filePath)
            );
        }
    
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \InvalidArgumentException(
                sprintf('Unable to open file: %s for reading', $filePath)
            );
        }

        $firstLine = fgetcsv($handle);
        fclose($handle);
    
        if ($firstLine === false) {
            throw new \InvalidArgumentException(
                sprintf('Unable to parse CSV headers from: %s', $filePath)
            );
        }
    
        $missing = array_diff($requiredHeaders, array_map('trim', $firstLine));
    
        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                sprintf('CSV is missing required headers: %s', implode(', ', $missing))
            );
        }
    }

    private function validateTrackingInfo(array $trackingInfo) {

        if (empty($trackingInfo)) {
            throw new \InvalidArgumentException('trackingInfo must not be empty');
        }

        foreach($trackingInfo as $item) {
            if(!is_array($item) || !isset($item['url']) || !is_string($item['url']) || trim($item['url']) === '') {
                throw new \InvalidArgumentException('Each tracking info item must be an array and contain a "url" key that is a non-empty string');
            }
        }
    }

    private function validateId($id, $callerName = '') {
        if ( is_bool($id) || 
            !is_scalar($id) ||
            (is_string($id) && trim($id) === '') ||
            (is_numeric($id) && $id < 0) ||
            (is_string($id) && !ctype_alnum($id)) ) {
            throw new TPException(sprintf('Second argument of Api::%s() should be interpolated safely to a string and not be boolean', $callerName));
        }
    }
}
