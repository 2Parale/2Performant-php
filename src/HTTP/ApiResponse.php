<?php

namespace TPerformant\API\HTTP;

use TPerformant\API\Exception\APIException;
use TPerformant\API\HTTP\User;
use TPerformant\API\Model\AffiliateProgram;

class ApiResponse implements AuthInterface {
    private $rawResponse = null;
    private $meta;
    private $body;
    private $owner = null;

    public function __construct($response, $expected, User $user = null) {
        $this->rawResponse = $response;
        $this->owner = $user;

        if(($response->getStatusCode() < 200) || ($response->getStatusCode() >= 300 )) {
            throw new APIException('Unsuccessful API call. Response status: '.$response->getStatusCode(), 0, null, $response);
        }

        $data = json_decode($response->getBody());

        if(isset($data->metadata)) {
            $this->meta = $data->metadata;
            unset($data->metadata);
        }

        $this->body = $this->_convert($data->$expected, $expected);
    }

    public function getBody() {
        return $this->body;
    }

    public function getMeta() {
        return $this->meta;
    }

    public function getHeader($header) {
        if($this->rawResponse->hasHeader($header)) {
            return $this->rawResponse->getHeader($header);
        }

        return false;
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

    public function getResponse() {
        return $this->rawResponse;
    }


    private function _convert($data, $expected) {
        if($this->owner && in_array($this->owner->getRole(), ['affiliate', 'advertiser'])) {
            $className = $this->getClassName($this->owner->getRole() . '_' . $expected);

            if(class_exists($className)) {
                return new $className($data, $this->owner);
            } elseif(substr($className, -1) == 's' && class_exists(substr($className, 0, -1))) {
                $className = substr($className, 0, -1);
                return array_map(function($element) use ($className) { return new $className($element, $this->owner); }, $data);
            }
        }

        $className = $this->getClassName($expected);

        if(class_exists($className)) {
            return new $className($data, $this->owner);
        } elseif(substr($className, -1) == 's' && class_exists(substr($className, 0, -1))) {
            $className = substr($className, 0, -1);
            return array_map(function($element) use ($className) { return new $className($element, $this->owner); }, $data);
        }

        return $data;
    }

    private function getClassName($resourceName) {
        $resourceName = preg_replace_callback('/\_([a-z])/', function($matches) { return strtoupper($matches[1]); }, $resourceName);

        return str_replace('\\HTTP', '\\Model', __NAMESPACE__) . '\\' . ucfirst($resourceName);
    }
}
