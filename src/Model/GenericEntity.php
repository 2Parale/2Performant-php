<?php

namespace TPerformant\API\Model;

use TPerformant\API\HTTP\User;

/**
 * Generic container for API response objects
 */
abstract class GenericEntity {
    /**
     * The credentials used for making API calls
     * @var HTTP\User
     */
    protected $requester;

    /**
     * Constructor
     * @param mixed     $data      The raw API response
     * @param HTTP\User $requester The authenticated user who made the call
     */
    public function __construct($data, User $requester = null) {
        $this->requester = $requester;

        $this->__constructFromObject($data);
    }

    /**
     * Parse stdClass object and populate relevant fields
     * @param  stdClass $object API response generic object
     */
    protected function __constructFromObject($object) {
        $classMap = $this->classMap();

        if(is_object($object)) {
            foreach($object as $key => $value) {
                $ownKey = $this->_convertKey($key);
                if(property_exists($this, $ownKey)) {
                    $className = isset($classMap[$ownKey]) ?  $this->getClassName($classMap[$ownKey]) : false;
                    $this->$ownKey = $className ? (new $className($value, $this->requester)) : $value;
                }
            }
        }
    }

    /**
     * Converts an API response key from underscore notation to camel case notation
     * @param  string   $key    Original key
     *
     * @return string           Converted notation
     */
    private function _convertKey($key) {
        $result = preg_replace_callback('/\_([a-z])/', function($matches) { return strtoupper($matches[1]); }, $key);

        return $result;
    }

    /**
     * Given a certain response resource name, this method checks whether there
     * is a corresponding class that the resource can be converted to
     * @param  string $resourceName Original resource namespace
     *
     * @return string               Found class name
     */
    private function getClassName($resourceName) {
        if($this->requester) {
            $className = __NAMESPACE__ . '\\' . ucfirst($this->requester->getRole()) . ucfirst($resourceName);
            if(class_exists($className))
                return $className;
        }

        $className = __NAMESPACE__ . '\\' . ucfirst($resourceName);
        if(class_exists($className))
            return $className;

        return false;
    }

    /**
     * Class map associative array that tells __constructFromObject how to treat
     * certain response fields. Will be overwritten in child classes.
     * @return array Class map
     */
    protected function classMap() {
        return [];
    }

    public function __call($method, $args) {
        // getProperty(), where property is defined as protected in subclasses
        if(preg_match('/get([a-zA-Z]+)/', $method, $matches)) {
            $property = lcfirst($matches[1]);
            if(property_exists($this, $property)) {
                return $this->$property;
            }
        }

        trigger_error('Call to undefined method '.__CLASS__.'::'.$method.'()', E_USER_ERROR);
    }
}
