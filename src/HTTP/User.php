<?php

namespace TPerformant\API\HTTP;

use TPerformant\API\Api;

abstract class User implements AuthInterface {
    protected $id;
    protected $email;
    protected $login;
    protected $name;
    protected $role;
    protected $uniqueCode;
    protected $createdAt;
    protected $avatarUrl;
    protected $newsletterSubscription;
    protected $userInfo = null;

    private $accessToken;
    private $clientToken;
    private $uid;

    private $userData = null;

    public function __construct($email = '', $password = '') {
        $result = Api::getInstance()->signIn($email, $password);

        $this->updateAuthTokens($result);

        $this->userData = $result->getBody();
    }

    public function updateAuthTokens(ApiResponse $result) {
        $this->accessToken = $result->getAccessToken();
        $this->clientToken = $result->getClientToken();
        $this->uid = $result->getUid();
    }

    public function updateAuthTokensAndReturn(ApiResponse $result) {
        $this->updateAuthTokens($result);

        return $result->getBody();
    }

    public function getRole() {
        return $this->userData->getRole();
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
