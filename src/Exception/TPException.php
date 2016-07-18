<?php

namespace TPerformant\API\Exception;

class TPException extends \Exception {
    private $data = null;

    public function __construct($message = '', $code = 0, $prev = null, $data = null) {
        parent::__construct($message, $code, $prev);
        $this->data = $data;
    }

    public function getData() {
        return $this->data;
    }
}
