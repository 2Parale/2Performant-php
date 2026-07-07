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

class RequestMultipartTest extends TestCase
{
    private array $requestHistory = [];

    private function createApiWithMockHttp(array $responses): Api
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);

        // Buffer the stream so Middleware::history can read the body after the handler consumes it
        $handlerStack->push(function (callable $handler) {
            return function (\Psr\Http\Message\RequestInterface $request, array $options) use ($handler) {
                $contents = $request->getBody()->getContents();
                $request = $request->withBody(\GuzzleHttp\Psr7\Utils::streamFor($contents));
                return $handler($request, $options);
            };
        }, 'buffer_body');

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

    private function buildMultipart(): array
    {
        return [
            [
                'name' => 'file_field',
                'contents' => 'file content here',
                'filename' => 'test.txt',
            ],
        ];
    }

    // --- Content-Type ---

    public function testContentTypeIsMultipartFormData(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['ok' => true])),
        ]);

        $api->requestMultipart('POST', '/some/endpoint', $this->buildMultipart());

        $contentType = $this->requestHistory[0]['request']->getHeaderLine('Content-Type');
        $this->assertStringContainsString('multipart/form-data', $contentType);
    }

    public function testContentTypeIncludesBoundary(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['ok' => true])),
        ]);

        $api->requestMultipart('POST', '/some/endpoint', $this->buildMultipart());

        $contentType = $this->requestHistory[0]['request']->getHeaderLine('Content-Type');
        $this->assertStringContainsString('boundary=', $contentType);
    }

    // --- Auth headers ---

    public function testSendsAuthHeadersWhenAuthProvided(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['ok' => true])),
        ]);

        $api->requestMultipart('POST', '/some/endpoint', $this->buildMultipart(), $this->createMockAuth());

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('test-access-token', $request->getHeaderLine('access-token'));
        $this->assertSame('test-client-token', $request->getHeaderLine('client'));
        $this->assertSame('test@example.com', $request->getHeaderLine('uid'));
    }

    public function testDoesNotSendAuthHeadersWhenAuthIsNull(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['ok' => true])),
        ]);

        $api->requestMultipart('POST', '/some/endpoint', $this->buildMultipart());

        $request = $this->requestHistory[0]['request'];
        $this->assertEmpty($request->getHeaderLine('access-token'));
        $this->assertEmpty($request->getHeaderLine('client'));
        $this->assertEmpty($request->getHeaderLine('uid'));
    }

    // --- Request ---

    public function testAppendsJsonSuffixToRoute(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['ok' => true])),
        ]);

        $api->requestMultipart('POST', '/some/endpoint', $this->buildMultipart());

        $this->assertSame('/some/endpoint.json', $this->requestHistory[0]['request']->getUri()->getPath());
    }

    public function testBodyContainsMultipartFieldName(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['ok' => true])),
        ]);

        $api->requestMultipart('POST', '/some/endpoint', $this->buildMultipart());

        $body = (string) $this->requestHistory[0]['request']->getBody();
        $this->assertStringContainsString('name="file_field"', $body);
    }

    public function testBodyContainsMultipartFieldContent(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['ok' => true])),
        ]);

        $api->requestMultipart('POST', '/some/endpoint', $this->buildMultipart());

        $body = (string) $this->requestHistory[0]['request']->getBody();
        $this->assertStringContainsString('file content here', $body);
    }

    // --- Success response ---

    public function testReturnsPsr7ResponseOnSuccess(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['ok' => true])),
        ]);

        $response = $api->requestMultipart('POST', '/some/endpoint', $this->buildMultipart());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testReturns201StatusCodeOnCreated(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], json_encode(['ok' => true])),
        ]);

        $response = $api->requestMultipart('POST', '/some/endpoint', $this->buildMultipart());

        $this->assertSame(201, $response->getStatusCode());
    }

    // --- throwOnErrorResponse ---

    public function testThrowsApiExceptionOn400(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(400, [], json_encode(['errors' => ['Bad Request']])),
        ]);

        $this->expectException(APIException::class);

        $api->requestMultipart('POST', '/some/endpoint', $this->buildMultipart());
    }

    public function testThrowsApiExceptionOn422WithErrorMessage(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(422, [], json_encode(['errors' => ['Validation failed']])),
        ]);

        $this->expectException(APIException::class);
        $this->expectExceptionMessageMatches('/Validation failed/');

        $api->requestMultipart('POST', '/some/endpoint', $this->buildMultipart());
    }

    public function testThrowsApiExceptionOn401(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(401, [], json_encode(['errors' => [['error' => 'You need to sign in before continuing.']]])),
        ]);

        $this->expectException(APIException::class);
        $this->expectExceptionMessageMatches('/sign in/');

        $api->requestMultipart('POST', '/some/endpoint', $this->buildMultipart());
    }

    public function testApiExceptionContainsHttpStatusCode(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(422, [], json_encode(['errors' => ['Some error']])),
        ]);

        try {
            $api->requestMultipart('POST', '/some/endpoint', $this->buildMultipart());
            $this->fail('Expected APIException was not thrown');
        } catch (APIException $e) {
            $this->assertSame(422, $e->getCode());
        }
    }

    public function testFallsBackToReasonPhraseWhenNoErrorsKeyInBody(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(400, [], json_encode(['message' => 'something went wrong'])),
        ]);

        $this->expectException(APIException::class);
        $this->expectExceptionMessageMatches('/Bad Request/');

        $api->requestMultipart('POST', '/some/endpoint', $this->buildMultipart());
    }
}
