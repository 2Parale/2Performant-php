<?php

namespace TPerformant\API\Tests\Api;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\Exception\APIException;
use TPerformant\API\Exception\ConnectionException;
use TPerformant\API\Exception\ServerException;
use TPerformant\API\Exception\TransferException;

class ExecuteRequestTest extends TestCase
{
    private function createApiWithMockHttp(array $responses): Api
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);

        return new Api('https://api.2performant.com', [
            'http' => ['handler' => $handlerStack],
        ]);
    }

    private function stubRequest(): Request
    {
        return new Request('GET', 'https://api.2performant.com/');
    }

    // --- Guzzle ServerException (5xx) → TP ServerException ---

    public function testGuzzleServerExceptionIsWrappedInServerException(): void
    {
        $api = $this->createApiWithMockHttp([new Response(500, [], 'Internal Server Error')]);

        $this->expectException(ServerException::class);

        $api->requestRaw('GET', '/some/endpoint');
    }

    public function testServerExceptionCarriesHttpStatusCode(): void
    {
        $api = $this->createApiWithMockHttp([new Response(503, [], 'Service Unavailable')]);

        try {
            $api->requestRaw('GET', '/some/endpoint');
            $this->fail('Expected ServerException was not thrown');
        } catch (ServerException $e) {
            $this->assertSame(503, $e->getCode());
        }
    }

    // --- Guzzle ConnectException → TP ConnectionException ---

    public function testGuzzleConnectExceptionIsWrappedInConnectionException(): void
    {
        $api = $this->createApiWithMockHttp([
            new ConnectException('Connection refused', $this->stubRequest()),
        ]);

        $this->expectException(ConnectionException::class);

        $api->requestRaw('GET', '/some/endpoint');
    }

    public function testConnectionExceptionIsSubclassOfTransferException(): void
    {
        // Callers catching the broader TransferException also catch ConnectionException
        $api = $this->createApiWithMockHttp([
            new ConnectException('Connection refused', $this->stubRequest()),
        ]);

        $this->expectException(TransferException::class);

        $api->requestRaw('GET', '/some/endpoint');
    }

    // --- Guzzle TransferException (other) → TP TransferException ---

    public function testGuzzleTransferExceptionIsWrappedInTransferException(): void
    {
        $api = $this->createApiWithMockHttp([
            new TooManyRedirectsException('Too many redirects', $this->stubRequest()),
        ]);

        $this->expectException(TransferException::class);

        $api->requestRaw('GET', '/some/endpoint');
    }

    public function testNonConnectTransferExceptionIsNotWrappedAsConnectionException(): void
    {
        $api = $this->createApiWithMockHttp([
            new TooManyRedirectsException('Too many redirects', $this->stubRequest()),
        ]);

        try {
            $api->requestRaw('GET', '/some/endpoint');
            $this->fail('Expected TransferException was not thrown');
        } catch (TransferException $e) {
            $this->assertNotInstanceOf(ConnectionException::class, $e);
        }
    }

    // --- throwOnErrorResponse: structured error objects ---

    public function testThrowsApiExceptionWithStructuredErrorTitleOnly(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(422, [], json_encode(['errors' => [['title' => 'Record not found']]])),
        ]);

        $this->expectException(APIException::class);
        $this->expectExceptionMessageMatches('/Record not found/');

        $api->requestRaw('GET', '/some/endpoint');
    }

    public function testThrowsApiExceptionWithStructuredErrorTitleAndDetail(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(422, [], json_encode(['errors' => [['title' => 'Invalid field', 'detail' => 'must be a number']]])),
        ]);

        $this->expectException(APIException::class);
        $this->expectExceptionMessageMatches('/Invalid field - must be a number/');

        $api->requestRaw('GET', '/some/endpoint');
    }

    public function testThrowsApiExceptionWithStructuredErrorTitleAndDetails(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(422, [], json_encode(['errors' => [['title' => 'Validation error', 'details' => 'amount is required']]])),
        ]);

        $this->expectException(APIException::class);
        $this->expectExceptionMessageMatches('/Validation error - amount is required/');

        $api->requestRaw('GET', '/some/endpoint');
    }

    public function testThrowsApiExceptionWithStructuredErrorUsingErrorKey(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(422, [], json_encode(['errors' => [['error' => 'field_invalid']]])),
        ]);

        $this->expectException(APIException::class);
        $this->expectExceptionMessageMatches('/field_invalid/');

        $api->requestRaw('GET', '/some/endpoint');
    }

    // --- throwOnErrorResponse: non-array errors value ---

    public function testThrowsApiExceptionWhenErrorsValueIsNonArray(): void
    {
        // errors is a scalar string, not an array; the loop is skipped and
        // the exception is still raised with the correct HTTP status code
        $api = $this->createApiWithMockHttp([
            new Response(400, [], json_encode(['errors' => 'Unauthorized'])),
        ]);

        $this->expectException(APIException::class);
        $this->expectExceptionCode(400);

        $api->requestRaw('GET', '/some/endpoint');
    }
}
