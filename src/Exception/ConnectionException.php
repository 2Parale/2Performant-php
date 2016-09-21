<?php

namespace TPerformant\API\Exception;

class ConnectionException extends TransferException {
    public static function create(\GuzzleHttp\Exception\ConnectException $e) {
        $message = sprintf(
            'Connection error %s on %s',
            $e->getMessage(),
            $e->getRequest()->getUri()
        );

        return new self($message, 0, $e);
    }
}
