<?php

namespace TPerformant\API\Tests\Api;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\HTTP\Affiliate;
use TPerformant\API\Exception\TPException;

class GetAffiliateProductsTest extends TestCase
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
        $affiliate = $this->createMock(Affiliate::class);
        $affiliate->method('getAccessToken')->willReturn('test-access-token');
        $affiliate->method('getClientToken')->willReturn('test-client-token');
        $affiliate->method('getUid')->willReturn('test@example.com');
        $affiliate->method('getRole')->willReturn('affiliate');

        return $affiliate;
    }

    public function testGetAffiliateProductsByIdSendsGetRequestWithCorrectUrl(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['products' => [['id' => 'abc123']]])),
        ]);

        $api->getAffiliateProducts($this->createMockAffiliate(), 'abc123');

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame(
            '/affiliate/product_feeds/abc123/products.json',
            $request->getUri()->getPath()
        );
    }

    public function testGetAffiliateProductsByIdRaisesExceptionForInvalidId(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['program' => ['id' => 'abc123']])),
        ]);

        $this->expectException(TPException::class);        
        $this->expectExceptionMessage('Parameter id passed to Api::getAffiliateProducts() must be a positive integer or an alphanumeric slug.');

        $api->getAffiliateProducts($this->createMockAffiliate(), []);
    }
}
