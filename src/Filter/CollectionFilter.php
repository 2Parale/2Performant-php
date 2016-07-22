<?php

namespace TPerformant\API\Filter;

abstract class CollectionFilter {

    private $page;
    private $perpage;

    private $filteredFields;

    public function __call($method, $args) {
        if(preg_match('/^([a-zA-Z]+)$/', $method, $matches)) {
            $fields = $this->filterableFields();
            if(isset($fields[$matches[1]])) {
                $this->filteredFields[$fields[$matches[1]]] = $args[0];

                return $this;
            }
        }

        trigger_error('Call to undefined method '.__CLASS__.'::'.$method.'()', E_USER_ERROR);
    }

    public function page($page) {
        $this->page = (int) $page;

        return $this;
    }

    public function perpage($perpage) {
        $this->perpage = (int) $perpage;

        return $this;
    }

    protected abstract function filterableFields();

    public function toParams() {
        $filters = [];

        if($this->page)
            $filters['page'] = $this->page;

        if($this->perpage)
            $filters['perpage'] = $this->perpage;

        if(!empty($this->filteredFields))
            $filters['filter'] = $this->filteredFields;

        return $filters;
    }
}
