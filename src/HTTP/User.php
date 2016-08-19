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
     * @param mixed  $email    Account email OR SavedSession object
     * @param string $password Account password
     */
    public function __construct($email = '', $password = '') {
        if($email && $password) {
            $auth = $this->signIn($email, $password);

            $this->userData = $this->updateAuthTokensAndReturn($auth);
        } else {
            if($email && is_object($email) && is_a($email, '\\TPerformant\\API\\HTTP\\SavedSession')) {
                $this->updateAuthTokens($email);

                $result = $this->validateToken();

                $this->userData = $result->getBody();
            }
        }
    }

    /**
     * Use response data to save authentication tokens
     * @param  ApiResponse $result API response
     */
    public function updateAuthTokens(AuthInterface $result) {
        $this->accessToken = $result->getAccessToken();
        $this->clientToken = $result->getClientToken();
        $this->uid = $result->getUid();
    }

    /**
     * Update authorization tokens and return the expected objects in the API response
     * @param  AuthInterface $result The parsed API response
     * @return mixed                 The structured API response data
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

    /**
     * Requests a new set of authentication tokens, given the user's email and password
     * @param  string $email    User email
     * @param  string $password User password
     * @return ApiResponse           The user data
     */
    public function signIn($email, $password) {
        return Api::getInstance()->signIn($email, $password);
    }

    /**
     * Validate a set of authentication tokens
     * @return ApiResponse The user details, if the tokens are valid
     */
    public function validateToken() {
        return Api::getInstance()->validateToken($this);
    }
}
