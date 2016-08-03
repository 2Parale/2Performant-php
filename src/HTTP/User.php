<?php

namespace TPerformant\API\HTTP;

use TPerformant\API\Api;

/**
 * Generic class for API access authentication/authorization
 */
abstract class User implements AuthInterface {
    private $accessToken;
    private $clientToken;
    private $uid;

    /**
     * API response user data
     * @var TPerformant\API\Model\User
     */
    private $userData = null;


    /**
     * Constructor used to sign into the API
     * @param string $email    Account email
     * @param string $password Account password
     */
    public function __construct($email = '', $password = '') {
        $result = Api::getInstance()->signIn($email, $password);

        $this->updateAuthTokens($result);

        $this->userData = $result->getBody();
    }

    /**
     * Use response data to save authentication tokens
     * @param  ApiResponse $result API response
     */
    public function updateAuthTokens(ApiResponse $result) {
        $this->accessToken = $result->getAccessToken();
        $this->clientToken = $result->getClientToken();
        $this->uid = $result->getUid();
    }

    /**
     * Update authorization tokens and return the expected objects in the API response
     * @param  ApiResponse $result The parsed API response
     * @return mixed               The structured API response data
     */
    public function updateAuthTokensAndReturn(ApiResponse $result) {
        $this->updateAuthTokens($result);

        return $result->getBody();
    }

    /**
     * Get role (affiliate/advertiser) of the user corresponding to the authentication token
     * @return string User role
     */
    public function getRole() {
        return $this->userData->getRole();
    }

    public function getUserData() {
        return $this->userData;
    }


    // AuthInterface methods

    public function getAccessToken() {
        return $this->accessToken;
    }

    public function getClientToken() {
        return $this->clientToken;
    }

    public function getUid() {
        return $this->uid;
    }


    // API methods

}
