<?php

namespace TPerformant\API;

class Api {
    private $http = null;

    public function __construct() {
        // TODO initialize $this->http transport layer
    }

    public function signIn($email, $password) {
        return $this->post('/users/sign_in', [
            'email' => $email,
            'password' => $password
        ]);
    }

    // Public methods
    // Advertiser methods
    // Affiliate methods

    // General request method
    public function request($method, $route, $params) {
        // TODO
    }

    public function get($route, $params) {
        return $this->request('GET', $route, $params);
    }

    public function post($route, $params) {
        return $this->request('POST', $route, $params);
    }

    public function put($route, $params) {
        return $this->request('PUT', $route, $params);
    }

    public function delete($route, $params) {
        return $this->request('DELETE', $route, $params);
    }

    // Singleton stuff
    private static $_api = null;

    public static function getInstance() {
        if(null === self::$_api) {
            self::$_api = new self();
        }

        return self::$_api;
    }
}
