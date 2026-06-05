<?php

namespace TPerformant\API\Tests\Api;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\HTTP\Advertiser;
use TPerformant\API\Exception\TPException;

class AcceptAdvertiserCommissionTest extends TestCase
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

    private function createMockAdvertiser(): Advertiser
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser->method('getAccessToken')->willReturn('access-token');
        $advertiser->method('getClientToken')->willReturn('client-token');
        $advertiser->method('getUid')->willReturn(42);
        $advertiser->method('getRole')->willReturn('advertiser');
        return $advertiser;
    }

    public function testAcceptAdvertiserCommissionSendsPutRequestWithCorrectUrl(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commission' => ['id' => 'abc123']])),
        ]);

        $api->acceptAdvertiserCommission($this->createMockAdvertiser(), 'abc123');

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('PUT', $request->getMethod());
        $this->assertSame(
            '/advertiser/programs/default/commissions/abc123/accept.json',
            $request->getUri()->getPath()
        );
    }

    public function testAcceptAdvertiserCommissionByIdRaisesExceptionForInvalidId(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commission' => ['id' => 'abc123']])),
        ]);

        $this->expectException(TPException::class);        
        $this->expectExceptionMessage('Second argument of Api::acceptAdvertiserCommission() should be interpolated safely to a string and not be boolean');

        $api->acceptAdvertiserCommission($this->createMockAdvertiser(), []);
    }
}
