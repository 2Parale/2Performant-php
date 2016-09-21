<?php

namespace TPerformant\API\Exception;

class ServerException extends APIException {
    public static function create(\GuzzleHttp\Exception\ServerException $e) {
        $message = sprintf(
            'API server error (%s %s): %s on %s',
            $e->getResponse()->getStatusCode(),
            $e->getResponse()->getReasonPhrase(),
            $e->getMessage(),
            $e->getRequest()->getUri()
        );

        return new self($message, $e->getResponse()->getStatusCode(), $e);
    }
}
