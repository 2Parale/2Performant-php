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

    // --- validateResponse() returns decoded data, not the response object ---

    public function testValidateResponseReturnsDecodedJsonData(): void
    {
        $payload = ['result' => 'ok', 'count' => 3];
        $response = new Response(200, [], json_encode($payload));

        $data = ApiResponse::validateResponse($response);

        $this->assertIsObject($data);
        $this->assertObjectHasProperty('result', $data);
        $this->assertSame('ok', $data->result);
        $this->assertSame(3, $data->count);
    }

    public function testValidateResponseReturnsNullForNoContent(): void
    {
        $response = new Response(204, [], '');

        $data = ApiResponse::validateResponse($response);

        $this->assertNull($data);
    }

    // --- getHeader() returns a string, not an array ---

    public function testGetHeaderReturnsStringValue(): void
    {
        $response = new Response(200, ['access-token' => 'my-token'], json_encode(['result' => 'ok']));
        $apiResponse = new ApiResponse($response, 'result');

        $value = $apiResponse->getHeader('access-token');

        $this->assertIsString($value);
        $this->assertSame('my-token', $value);
    }

    public function testGetHeaderReturnsFalseWhenAbsent(): void
    {
        $response = new Response(200, [], json_encode(['result' => 'ok']));
        $apiResponse = new ApiResponse($response, 'result');

        $this->assertFalse($apiResponse->getHeader('non-existent-header'));
    }

    public function testGetHeaderReturnsFirstValueWhenMultiplePresent(): void
    {
        $response = new Response(200, ['x-custom' => ['first', 'second']], json_encode(['result' => 'ok']));
        $apiResponse = new ApiResponse($response, 'result');

        $value = $apiResponse->getHeader('x-custom');

        $this->assertSame('first', $value);
    }
}
