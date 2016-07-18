<?php

namespace TPerformant\API\Model;

use TPerformant\API\Api;
use TPerformant\API\HTTP\ApiResult;

abstract class User {
    private $id;
    private $email;
    private $login;
    private $name;
    private $role;
    private $uniqueCode;
    private $createdAt;
    private $avatarUrl;
    private $newsletterSubscription;
    private $userInfo = null;

    private $accessToken;
    private $clientToken;
    private $uid;

    public function __construct($email = '', $password = '') {
        // TODO API call to login

        $result = Api::getInstance()->signIn($email, $password);

        $this->updateAuthTokens($result);

        $result = $result->getBody();

        foreach(['id', 'email', 'login', 'name', 'role', 'uniqueCode', 'createdAt', 'avatarUrl', 'newsletterSubscription'] as $key) {
            $this->$key = $result[$key];
        }

        $this->userInfo = new \object();

        foreach([] as $key) {
            $this->userInfo->$key = $result['user_info'][$key];
        }
    }

    public function updateAuthTokens(ApiResult $result) {
        $this->accessToken = $result->getAccessToken();
        $this->clientToken = $result->getClientToken();
        $this->uid = $result->getUid();
    }

    public function updateAuthTokensAndReturn(ApiResult $result) {
        $this->updateAuthTokens($result);

        return $result->getBody();
    }

    public function getId()
	{
		return $this->id;
	}

    public function getEmail()
	{
		return $this->email;
	}

    public function getLogin()
	{
		return $this->login;
	}

    public function getName()
	{
		return $this->name;
	}

    public function getRole()
	{
		return $this->role;
	}

    public function getUniqueCode()
	{
		return $this->uniqueCode;
	}

    public function getCreatedAt()
	{
		return $this->createdAt;
	}

    public function getAvatarUrl()
	{
		return $this->avatarUrl;
	}

    public function getNewsletterSubscription()
	{
		return $this->newsletterSubscription;
	}

    public function getUserInfo()
	{
		return $this->userInfo;
	}


    public function getPrograms() {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getPrograms($this));
    }

    public function getProgram($id) {
        return $this->updateAuthTokensAndReturn(Api::getInstance()->getProgram($this, $id));
    }

}
