<?php

namespace TPerformant\API\HTTP;

use TPerformant\API\Exception\APIException;
use TPerformant\API\Exception\ClientException;
use TPerformant\API\Exception\InvalidResponseException;
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

        $response = self::validateResponse($response);

        $data = json_decode($response->getBody());

        if(isset($data->metadata)) {
            $this->meta = $data->metadata;
            unset($data->metadata);

            if(isset($this->meta->deprecations) && (is_array($this->meta->deprecations) || $this->meta->deprecations instanceof Traversable)) {
                foreach($this->meta->deprecations as $deprecation) {
                    $message = '';
                    if(isset($deprecation->title)) {
                        $message .= $deprecation->title . ' ';
                    }
                    if(isset($deprecation->detail)) {
                        $message .= $deprecation->detail . ' ';
                    }

                    trigger_error($message, E_USER_DEPRECATED);
                }
            }
        }

        if(!isset($data->$expected)) {
            throw new InvalidResponseException(
                sprintf('Response does not contain expected property (%s)', $expected)
            );
        }

        $this->body = $this->_convert($data->$expected, $expected);
    }

    /**
     * Checks if a HTTP response is a valid API response
     * @param  PsrHttpMessageResponseInterface $response The response to check
     * @return PsrHttpMessageResponseInterface           The same response
     * @throws InvalidResponseException
     */
    public static function validateResponse(\Psr\Http\Message\ResponseInterface $response) {
        $data = json_decode($response->getBody());

        if($response->getStatusCode() >= 400 && $response->getStatusCode() < 500) {
            if(false === $data || !is_object($data)) {
                throw new ClientException('Your request returned an error: '.$response->getStatusCode().' '.$response->getReasonPhrase(), $response->getStatusCode());
            }
        }

        if(($response->getStatusCode() < 200) || ($response->getStatusCode() >= 300 && $response->getStatusCode() < 400) || ($response->getStatusCode() >= 500)) {
            throw new APIException('Unsuccessful API call. Response status: '.$response->getStatusCode().' '.$response->getReasonPhrase(), $response->getStatusCode(), null, $response);
        }

        if(null === $data && $response->getStatusCode() <> 204) {
            $body = $response->getBody();
            $summary = '';

            if ($body->isSeekable()) {
                $size = $body->getSize();
                $summary = $body->read(120);
                $body->rewind();

                if ($size > 120 && strlen($summary) > 120) {
                    $summary .= ' (truncated...)';
                }

                // Matches any printable character, including unicode characters:
                // letters, marks, numbers, punctuation, spacing, and separators.
                if (preg_match('/[^\pL\pM\pN\pP\pS\pZ\n\r\t]/', $summary)) {
                    $summary = '';
                }
            }

            $summary = $summary ? ' Body: ' . $summary : '';

            throw new InvalidResponseException('Response body must be valid JSON.'.$summary, 0, null, $response->getBody());
        }

        if($data && isset($data->errors) && (is_array($data->errors) || $data->errors instanceof Traversable)) {
            $message = [];
            foreach ($data->errors as $error) {
                $m = $error->title;
                if(isset($error->detail)) {
                    $m .= ' - ' . $error->detail;
                }
                if(isset($error->source) && $error->source instanceof Traversable) {
                    $source = [];
                    foreach($error->source as $k => $v) {
                        $source[] = $k.':'.$v;
                    }
                    if($source) {
                        $m .= ' ('.implode(';', $source).')';
                    }
                }

                $message[] = $m;
            }

            $message = 'API responded with code ' . $response->getStatusCode() . ' and the following error(s): ' . implode(', ', $message);
            throw new APIException($message, $response->getStatusCode());
        }

        return $response;
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
        // If we are expecting a user, set the class directly
        if('user' == $expected && isset($data->role)) {
            $className = $this->getClassName($data->role);

            if(class_exists($className)) {
                return new $className($data, $this->owner);
            }
        }

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
