<?php

namespace TPerformant\API\Model;

/**
 * Generic user continer for API response
 */
class User extends GenericEntity {
    protected $id;
    protected $email;
    protected $login;
    protected $name;
    protected $role;
    protected $uniqueCode;
    protected $createdAt;
    protected $avatarUrl;
    protected $newsletterSubscription;
    protected $userInfo;
}
