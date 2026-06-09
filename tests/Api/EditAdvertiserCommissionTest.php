<?php

namespace TPerformant\API\Tests\Api;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\HTTP\Advertiser;

class EditAdvertiserCommissionTest extends TestCase {
    private array $requestHistory = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestHistory = [];
    }

    private function createMockAdvertiser(): Advertiser
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser->method('getAccessToken')->willReturn('access-token');
        $advertiser->method('getClientToken')->willReturn('client-token');
        $advertiser->method('getUid')->willReturn(42);
        $advertiser->method('getRole')->willReturn('advertiser');
        return $advertiser;
    }

    private function createApiWithMockHttp(array $responses): Api
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($this->requestHistory));

        return new Api('https://api.2performant.com', [
            'http' => ['handler' => $handlerStack],
        ]);
    }

    public function testSendsPutWithAffiliateUserId(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commission' => ['id' => 1]])),
        ]);

        $api->editAdvertiserCommission($this->createMockAdvertiser(), 123, 15.50, ["amount" => 15.50, "currencyCode" => "EUR"], 'Updated description');

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('PUT', $request->getMethod());
        $this->assertSame(
            '/advertiser/programs/default/commissions/123.json',
            $request->getUri()->getPath()
        );

        $body = json_decode($request->getBody()->getContents(), true);
        $this->assertSame(15.50, $body['commission']['amount']);
        $this->assertSame('Updated description', $body['commission']['description']);
    }

    public function testEditAdvertiserCommissionWithInvalidIdRaisesException(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commission' => ['id' => 1]])),
        ]);

        $this->expectException(\TPerformant\API\Exception\TPException::class);
        $api->editAdvertiserCommission($this->createMockAdvertiser(), '', 15.50, ["amount" => 15.50, "currencyCode" => "EUR"], 'Updated description');
    }   
}
