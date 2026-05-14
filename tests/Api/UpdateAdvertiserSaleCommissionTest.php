<?php

namespace TPerformant\API\Tests\Api;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\HTTP\Advertiser;
use TPerformant\API\Model\Sale;

class UpdateAdvertiserSaleCommissionTest extends TestCase
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
        $advertiser->method('getAccessToken')->willReturn('test-access-token');
        $advertiser->method('getClientToken')->willReturn('test-client-token');
        $advertiser->method('getUid')->willReturn('test@example.com');
        $advertiser->method('getRole')->willReturn('advertiser');

        return $advertiser;
    }

    private function saleResponseBody(array $overrides = []): string
    {
        $defaults = [
            'id' => 42,
            'amount' => '150.00',
            'currency_code' => 'EUR',
            'amount_in_working_currency' => '150.00',
            'working_currency_code' => 'EUR',
        ];

        return json_encode(['sale' => array_merge($defaults, $overrides)]);
    }

    public function testSendsPutRequestToCorrectEndpoint(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], $this->saleResponseBody()),
        ]);

        $api->updateAdvertiserSaleCommission($this->createMockAdvertiser(), 123, '150.00', 'EUR', 'Price changed');

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('PUT', $request->getMethod());
        $this->assertSame(
            '/advertiser/programs/default/commissions/123/update_sale.json',
            $request->getUri()->getPath()
        );
    }

    public function testSendsCorrectRequestBody(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], $this->saleResponseBody()),
        ]);

        $api->updateAdvertiserSaleCommission($this->createMockAdvertiser(), 99, '250.50', 'USD', 'Customer refund');

        $request = $this->requestHistory[0]['request'];
        $body = json_decode($request->getBody()->getContents(), true);

        $this->assertSame([
            'sale' => [
                'amount' => '250.50',
                'currency_code' => 'USD',
                'reason' => 'Customer refund',
            ],
        ], $body);
    }

    public function testSendsAuthHeaders(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], $this->saleResponseBody()),
        ]);

        $api->updateAdvertiserSaleCommission($this->createMockAdvertiser(), 1, '10', 'EUR', 'test');

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('test-access-token', $request->getHeaderLine('access-token'));
        $this->assertSame('test-client-token', $request->getHeaderLine('client'));
        $this->assertSame('test@example.com', $request->getHeaderLine('uid'));
    }

    public function testReturnsSaleModelOnSuccess(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], $this->saleResponseBody([
                'amount' => '300.00',
                'currency_code' => 'RON',
            ])),
        ]);

        $result = $api->updateAdvertiserSaleCommission($this->createMockAdvertiser(), 5, '300.00', 'RON', 'Updated');

        $sale = $result->getBody();
        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertSame('300.00', $sale->getAmount());
        $this->assertSame('RON', $sale->getCurrencyCode());
    }

    public function testSaleModelMapsAllFields(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], $this->saleResponseBody([
                'id' => 77,
                'amount' => '500.00',
                'currency_code' => 'GBP',
                'amount_in_working_currency' => '2500.00',
                'working_currency_code' => 'RON',
            ])),
        ]);

        $result = $api->updateAdvertiserSaleCommission($this->createMockAdvertiser(), 77, '500.00', 'GBP', 'Bulk update');

        $sale = $result->getBody();
        $this->assertSame(77, $sale->getId());
        $this->assertSame('500.00', $sale->getAmount());
        $this->assertSame('GBP', $sale->getCurrencyCode());
        $this->assertSame('2500.00', $sale->getAmountInWorkingCurrency());
        $this->assertSame('RON', $sale->getWorkingCurrencyCode());
    }

    public function testUsesCommissionIdInEndpointUrl(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], $this->saleResponseBody()),
        ]);

        $api->updateAdvertiserSaleCommission($this->createMockAdvertiser(), 9876, '10', 'EUR', 'test');

        $request = $this->requestHistory[0]['request'];
        $this->assertStringContainsString('/commissions/9876/update_sale', $request->getUri()->getPath());
    }

    public function testThrowsApiExceptionOn422Response(): void
    {
        $errorBody = json_encode([
            'errors' => [
                ['title' => 'Validation failed', 'detail' => 'Amount must be positive'],
            ],
        ]);

        $api = $this->createApiWithMockHttp([
            new Response(422, [], $errorBody),
        ]);

        $this->expectException(\TPerformant\API\Exception\APIException::class);
        $this->expectExceptionMessageMatches('/Amount must be positive/');

        $api->updateAdvertiserSaleCommission($this->createMockAdvertiser(), 1, '-5', 'EUR', 'bad');
    }

    public function testThrowsClientExceptionOn404WithNonJsonBody(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(404, [], 'Not Found'),
        ]);

        $this->expectException(\TPerformant\API\Exception\ClientException::class);

        $api->updateAdvertiserSaleCommission($this->createMockAdvertiser(), 999, '10', 'EUR', 'missing');
    }

    public function testThrowsInvalidResponseWhenSaleKeyMissing(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commission' => ['id' => 1]])),
        ]);

        $this->expectException(\TPerformant\API\Exception\InvalidResponseException::class);
        $this->expectExceptionMessageMatches('/sale/');

        $api->updateAdvertiserSaleCommission($this->createMockAdvertiser(), 1, '10', 'EUR', 'test');
    }
}
