<?php

namespace TPerformant\API\HTTP;

use TPerformant\API\Exception\APIException;
use TPerformant\API\HTTP\User;
use TPerformant\API\Model\AffiliateProgram;

/**
 * API response wrapper class
 */
class ApiResponse implements AuthInterface {
    private $rawResponse = null;
    private $meta;
    private $body;
    private $owner = null;

    /**
     * Constructor
     * @param Guzzle\Psr7\Respone   $response   The API response
     * @param string                $expected   Key of main response container in response hash
     *
     * @param HTTP\User             $user       The user making the call
     */
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

    /**
     * Get parsed response body
     * @return stdClass The expected property/array of properties specified
     *                  by $expected, as found in the parsed response body
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Get parsed metadata
     * @return stdClass The parsed metadata of the current result set, if any
     */
    public function getMeta() {
        return $this->meta;
    }

    /**
     * Get header from API HTTP response
     * @param  string $header Header namespace
     * @return string         Header value
     */
    public function getHeader($header) {
        if($this->rawResponse->hasHeader($header)) {
            return $this->rawResponse->getHeader($header);
        }

        return false;
    }

    /**
     * Get the access token for the next request
     * @return string Access token
     */
    public function getAccessToken() {
        return $this->getHeader('access-token');
    }

    /**
     * Get the client hash for the next request
     * @return string Client hash/token
     */
    public function getClientToken() {
        return $this->getHeader('client');
    }

    /**
     * Get the uid for the next request
     * @return string The UID
     */
    public function getUid() {
        return $this->getHeader('uid');
    }

    /**
     * Get the raw respone
     * @return Guzzle\Psr7\Response Response
     */
    public function getResponse() {
        return $this->rawResponse;
    }


    /**
     * Converts a parsed response to a structured class defined among the models
     * @param  stdClass $data       Source unstructured data
     * @param  string   $expected   Expected value (singular or plural)
     * @return mixed                The structured data
     */
    private function _convert($data, $expected) {
        // Try AffiliateModelClass first
        if($this->owner && in_array($this->owner->getRole(), ['affiliate', 'advertiser'])) {
            $className = $this->getClassName($this->owner->getRole() . '_' . $expected);

            if(class_exists($className)) {
                return new $className($data, $this->owner);
            } elseif(substr($className, -1) == 's' && class_exists(substr($className, 0, -1))) {
                $className = substr($className, 0, -1);
                return array_map(function($element) use ($className) { return new $className($element, $this->owner); }, $data);
            }
        }

        // Try ModelClass
        $className = $this->getClassName($expected);

        if(class_exists($className)) {
            return new $className($data, $this->owner);
        } elseif(substr($className, -1) == 's' && class_exists(substr($className, 0, -1))) {
            $className = substr($className, 0, -1);
            return array_map(function($element) use ($className) { return new $className($element, $this->owner); }, $data);
        }

        // Nothing worked, return raw data
        return $data;
    }

    /**
     * Finds an existing class name based on the expected property name
     * @param  string $resourceName The resource name
     * @return string               The generated class name
     */
    private function getClassName($resourceName) {
        $resourceName = preg_replace_callback('/\_([a-z])/', function($matches) { return strtoupper($matches[1]); }, $resourceName);

        return str_replace('\\HTTP', '\\Model', __NAMESPACE__) . '\\' . ucfirst($resourceName);
    }
}
