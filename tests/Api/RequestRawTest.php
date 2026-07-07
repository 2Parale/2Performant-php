<?php

namespace TPerformant\API\Tests\Api;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use TPerformant\API\Api;
use TPerformant\API\Exception\APIException;
use TPerformant\API\HTTP\AuthInterface;

class RequestRawTest extends TestCase
{
    private array $requestHistory = [];

    private function createApiWithMockHttp(array $responses): Api
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($this->requestHistory));

        return new Api('https://api.2performant.com', [
            'http' => ['handler' => $handlerStack],
        ]);
    }

    private function createMockAuth(): AuthInterface
    {
        $auth = $this->createMock(AuthInterface::class);
        $auth->method('getAccessToken')->willReturn('test-access-token');
        $auth->method('getClientToken')->willReturn('test-client-token');
        $auth->method('getUid')->willReturn('test@example.com');

        return $auth;
    }

    // --- GET: query params ---

    public function testGetPassesParamsAsQueryString(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], 'raw content'),
        ]);

        $api->requestRaw('GET', '/some/endpoint', ['foo' => 'bar', 'baz' => 'qux']);

        $query = [];
        parse_str($this->requestHistory[0]['request']->getUri()->getQuery(), $query);
        $this->assertSame('bar', $query['foo']);
        $this->assertSame('qux', $query['baz']);
    }

    public function testGetRequestBodyIsEmpty(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], 'raw content'),
        ]);

        $api->requestRaw('GET', '/some/endpoint', ['foo' => 'bar']);

        $this->assertEmpty((string) $this->requestHistory[0]['request']->getBody());
    }

    public function testGetWithNoParamsSendsEmptyQueryString(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], 'raw content'),
        ]);

        $api->requestRaw('GET', '/some/endpoint');

        $this->assertEmpty($this->requestHistory[0]['request']->getUri()->getQuery());
    }

    // --- POST: JSON body ---

    public function testPostPassesParamsAsJsonBody(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], 'raw content'),
        ]);

        $api->requestRaw('POST', '/some/endpoint', ['key' => 'value', 'number' => 42]);

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('POST', $request->getMethod());

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('value', $body['key']);
        $this->assertSame(42, $body['number']);
    }

    public function testPostHasNoQueryString(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], 'raw content'),
        ]);

        $api->requestRaw('POST', '/some/endpoint', ['key' => 'value']);

        $this->assertEmpty($this->requestHistory[0]['request']->getUri()->getQuery());
    }

    // --- Route ---

    public function testAppendsJsonSuffixToRoute(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], 'raw content'),
        ]);

        $api->requestRaw('GET', '/some/endpoint');

        $this->assertSame('/some/endpoint.json', $this->requestHistory[0]['request']->getUri()->getPath());
    }

    // --- Auth headers ---

    public function testSendsAuthHeadersWhenAuthProvided(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], 'raw content'),
        ]);

        $api->requestRaw('GET', '/some/endpoint', [], $this->createMockAuth());

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('test-access-token', $request->getHeaderLine('access-token'));
        $this->assertSame('test-client-token', $request->getHeaderLine('client'));
        $this->assertSame('test@example.com', $request->getHeaderLine('uid'));
    }

    public function testDoesNotSendAuthHeadersWhenAuthIsNull(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], 'raw content'),
        ]);

        $api->requestRaw('GET', '/some/endpoint');

        $request = $this->requestHistory[0]['request'];
        $this->assertEmpty($request->getHeaderLine('access-token'));
        $this->assertEmpty($request->getHeaderLine('client'));
        $this->assertEmpty($request->getHeaderLine('uid'));
    }

    // --- Success response ---

    public function testReturnsPsr7ResponseOnSuccess(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], 'some content'),
        ]);

        $response = $api->requestRaw('GET', '/some/endpoint');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testResponseIsNotWrappedInApiResponse(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], 'some content'),
        ]);

        $response = $api->requestRaw('GET', '/some/endpoint');

        $this->assertNotInstanceOf(\TPerformant\API\HTTP\ApiResponse::class, $response);
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Response::class, $response);
    }

    // --- Error handling: throws APIException on 4xx ---

    public function testThrowsApiExceptionOn400(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(400, [], json_encode(['errors' => ['Bad request']])),
        ]);

        $this->expectException(APIException::class);

        $api->requestRaw('GET', '/some/endpoint');
    }

    public function testThrowsApiExceptionOn401(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(401, [], json_encode(['errors' => ['Unauthorized']])),
        ]);

        $this->expectException(APIException::class);

        $api->requestRaw('GET', '/some/endpoint');
    }

    public function testThrowsApiExceptionOn403(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(403, [], json_encode(['errors' => ['Forbidden']])),
        ]);

        $this->expectException(APIException::class);
        $this->expectExceptionMessageMatches('/Forbidden/');

        $api->requestRaw('GET', '/some/endpoint');
    }

    public function testThrowsApiExceptionOn422(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(422, [], json_encode(['errors' => ['Unprocessable entity']])),
        ]);

        $this->expectException(APIException::class);
        $this->expectExceptionMessageMatches('/Unprocessable entity/');

        $api->requestRaw('GET', '/some/endpoint');
    }

    public function testApiExceptionContainsHttpStatusCode(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(403, [], json_encode(['errors' => ['Forbidden']])),
        ]);

        try {
            $api->requestRaw('GET', '/some/endpoint');
            $this->fail('Expected APIException was not thrown');
        } catch (APIException $e) {
            $this->assertSame(403, $e->getCode());
        }
    }

    public function testFallsBackToReasonPhraseWhenNoErrorsKeyInBody(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(400, [], json_encode(['message' => 'something went wrong'])),
        ]);

        $this->expectException(APIException::class);
        $this->expectExceptionMessageMatches('/Bad Request/');

        $api->requestRaw('GET', '/some/endpoint');
    }

    public function testDoesNotThrowOn200(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], 'ok'),
        ]);

        $response = $api->requestRaw('GET', '/some/endpoint');

        $this->assertSame(200, $response->getStatusCode());
    }
}
