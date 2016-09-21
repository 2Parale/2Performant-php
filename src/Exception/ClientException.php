<?php

namespace TPerformant\API\Exception;

class ClientException extends APIException {
    public static function create(\GuzzleHttp\Exception\ClientException $e) {
        $message = sprintf(
            'Request could not be processed (%s %s): %s on %s',
            $e->getResponse()->getStatusCode(),
            $e->getResponse()->getReasonPhrase(),
            $e->getMessage(),
            $e->getRequest()->getUri()
        );

        return new self($message, $e->getResponse()->getStatusCode(), $e);
    }
}
