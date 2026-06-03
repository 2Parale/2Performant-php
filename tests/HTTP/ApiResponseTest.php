<?php

namespace TPerformant\API\Tests\HTTP;

use PHPUnit\Framework\TestCase;
use TPerformant\API\HTTP\ApiResponse;
use GuzzleHttp\Psr7\Response;
use TPerformant\API\Exception\APIException;

class ApiResponseTest extends TestCase {
    public function testValidateResponseParsesErrorsFromArray(): void
    {
        $errors = [
            ['title' => 'Validation failed', 'detail' => 'Amount invalid'],
        ];
        $response = new Response(422, [], json_encode(['errors' => $errors]));
        $this->expectException(APIException::class);
        $this->expectExceptionMessage('Validation failed');
        ApiResponse::validateResponse($response);
    }
}
