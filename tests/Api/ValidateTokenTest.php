<?php

namespace TPerformant\API\Tests\Api;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\HTTP\Affiliate;
use TPerformant\API\HTTP\ApiResponse;

class ValidateTokenTest extends TestCase
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

    private function createMockAffiliate(): Affiliate
    {
        $auth = $this->createMock(Affiliate::class);
        $auth->method('getAccessToken')->willReturn('test-access-token');
        $auth->method('getClientToken')->willReturn('test-client-token');
        $auth->method('getUid')->willReturn('test@example.com');

        return $auth;
    }

    private function tokenResponseBody(): string
    {
        return json_encode(['user' => ['id' => 1, 'email' => 'test@example.com', 'role' => 'affiliate']]);
    }

    // --- Request ---

    public function testSendsGetRequest(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], $this->tokenResponseBody()),
        ]);

        $api->validateToken($this->createMockAffiliate());

        $this->assertSame('GET', $this->requestHistory[0]['request']->getMethod());
    }

    public function testSendsToCorrectEndpoint(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], $this->tokenResponseBody()),
        ]);

        $api->validateToken($this->createMockAffiliate());

        $this->assertSame(
            '/users/validate_token.json',
            $this->requestHistory[0]['request']->getUri()->getPath()
        );
    }

    public function testSendsNoQueryParams(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], $this->tokenResponseBody()),
        ]);

        $api->validateToken($this->createMockAffiliate());

        $this->assertEmpty($this->requestHistory[0]['request']->getUri()->getQuery());
    }

    // --- Auth headers ---

    public function testSendsAccessTokenHeader(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], $this->tokenResponseBody()),
        ]);

        $api->validateToken($this->createMockAffiliate());

        $this->assertSame(
            'test-access-token',
            $this->requestHistory[0]['request']->getHeaderLine('access-token')
        );
    }

    public function testSendsClientTokenHeader(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], $this->tokenResponseBody()),
        ]);

        $api->validateToken($this->createMockAffiliate());

        $this->assertSame(
            'test-client-token',
            $this->requestHistory[0]['request']->getHeaderLine('client')
        );
    }

    public function testSendsUidHeader(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], $this->tokenResponseBody()),
        ]);

        $api->validateToken($this->createMockAffiliate());

        $this->assertSame(
            'test@example.com',
            $this->requestHistory[0]['request']->getHeaderLine('uid')
        );
    }

    // --- Response ---

    public function testReturnsApiResponse(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], $this->tokenResponseBody()),
        ]);

        $response = $api->validateToken($this->createMockAffiliate());

        $this->assertInstanceOf(ApiResponse::class, $response);
    }
}
