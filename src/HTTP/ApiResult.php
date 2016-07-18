<?php

namespace TPerformant\API\HTTP;

class ApiResult {
    public function __construct() {
        
    }

    public function getBody() {
        // TODO
    }

    public function getMeta() {
        // TODO
    }

    public function getHeader($header) {
        // TODO
    }

    public function getAccessToken() {
        return $this->getHeader('access-token');
    }

    public function getClientToken() {
        return $this->getHeader('client');
    }

    public function getUid() {
        return $this->getHeader('uid');
    }
}
