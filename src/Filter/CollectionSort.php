<?php

namespace TPerformant\API\Filter;

abstract class CollectionSort {
    protected $sortedFields = array();


    public function __call($method, $args) {
        if(preg_match('/^([a-zA-Z]+)(Asc|Desc)$/', $method, $matches)) {
            $fields = $this->sortableFields();
            if(isset($fields[$matches[1]])) {
                $this->sortedFields[$fields[$matches[1]]] = strtolower($matches[2]);

                return $this;
            }
        }

        trigger_error('Call to undefined method '.__CLASS__.'::'.$method.'()', E_USER_ERROR);
    }

    protected abstract function sortableFields();

    public function toParams() {
        return ['sort' => $this->sortedFields];
    }
}
