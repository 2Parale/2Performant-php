<?php

namespace TPerformant\API\Model;

use TPerformant\API\HTTP\User;

abstract class GenericEntity {
    protected $requester;

    public function __construct($data, User $requester = null) {
        $this->requester = $requester;

        $this->__constructFromObject($data);
    }

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

    private function _convertKey($key) {
        $result = preg_replace_callback('/\_([a-z])/', function($matches) { return strtoupper($matches[1]); }, $key);

        if('actionid' === $result)
            return 'actionId';

        return $result;
    }

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

    protected function classMap() {
        return [];
    }

    public function __call($method, $args) {
        if(preg_match('/get([a-zA-Z]+)/', $method, $matches)) {
            $property = lcfirst($matches[1]);
            if(property_exists($this, $property)) {
                return $this->$property;
            }
        }

        trigger_error('Call to undefined method '.__CLASS__.'::'.$method.'()', E_USER_ERROR);
    }
}
