<?php

namespace TPerformant\API\Tests\Api;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\HTTP\Affiliate;
use TPerformant\API\Filter\AffiliateAdvertiserPromotionFilter;
use TPerformant\API\Filter\AffiliateAdvertiserPromotionSort;

class GetAffiliatePromotionsExportTest extends TestCase
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

    private function csvResponseBody(): string
    {
        return "Program,Name,Start Date,End Date,Affiliate Description,Customer Description,Coupon,Landing Page,Benefits & Tools\n"
             . "Campaign 1,Summer Sale,2024-06-01,2024-08-31,Great discounts,Exclusive deals,SUMMER2024,https://example.com/summer,Bonuses & Special Banners\n";
    }

    // --- HTTP Request ---

    public function testSendsGetRequest(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $this->csvResponseBody()),
        ]);

        $api->getAffiliatePromotionsExport($this->createMockAffiliate());

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('GET', $request->getMethod());
    }

    public function testSendsToCorrectEndpoint(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $this->csvResponseBody()),
        ]);

        $api->getAffiliatePromotionsExport($this->createMockAffiliate());

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('/affiliate/advertiser_promotions/export.json', $request->getUri()->getPath());
    }

    public function testSendsAuthHeaders(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $this->csvResponseBody()),
        ]);

        $api->getAffiliatePromotionsExport($this->createMockAffiliate());

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('test-access-token', $request->getHeaderLine('access-token'));
        $this->assertSame('test-client-token', $request->getHeaderLine('client'));
        $this->assertSame('test@example.com', $request->getHeaderLine('uid'));
    }

    // --- Query Parameters ---

    public function testSendsNoQueryParamsWithoutFilterOrSort(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $this->csvResponseBody()),
        ]);

        $api->getAffiliatePromotionsExport($this->createMockAffiliate());

        $request = $this->requestHistory[0]['request'];
        $this->assertEmpty($request->getUri()->getQuery());
    }

    public function testSendsFilterParamsAsQueryString(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $this->csvResponseBody()),
        ]);

        $filter = new AffiliateAdvertiserPromotionFilter();
        $filter->affrequestStatus('accepted');

        $api->getAffiliatePromotionsExport($this->createMockAffiliate(), $filter);

        $request = $this->requestHistory[0]['request'];
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame('accepted', $query['filter']['affrequest_status']);
    }

    public function testSendsSortParamsAsQueryString(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $this->csvResponseBody()),
        ]);

        $sort = new AffiliateAdvertiserPromotionSort();
        $sort->promotionStartAsc();

        $api->getAffiliatePromotionsExport($this->createMockAffiliate(), null, $sort);

        $request = $this->requestHistory[0]['request'];
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame('asc', $query['sort']['promotion_start']);
    }

    public function testSendsBothFilterAndSortParams(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $this->csvResponseBody()),
        ]);

        $filter = new AffiliateAdvertiserPromotionFilter();
        $filter->affrequestStatus('accepted');

        $sort = new AffiliateAdvertiserPromotionSort();
        $sort->campaignNameDesc();

        $api->getAffiliatePromotionsExport($this->createMockAffiliate(), $filter, $sort);

        $request = $this->requestHistory[0]['request'];
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame('accepted', $query['filter']['affrequest_status']);
        $this->assertSame('desc', $query['sort']['campaign_name']);
    }

    public function testIgnoresPaginationParams(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $this->csvResponseBody()),
        ]);

        $filter = new AffiliateAdvertiserPromotionFilter();
        $filter->page(2)->perpage(10);

        $api->getAffiliatePromotionsExport($this->createMockAffiliate(), $filter);
        
        $request = $this->requestHistory[0]['request'];
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertArrayNotHasKey('page', $query);
        $this->assertArrayNotHasKey('perpage', $query);
    }

    // --- Success Response ---

    public function testReturnsPsr7ResponseOnSuccess(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $this->csvResponseBody()),
        ]);

        $response = $api->getAffiliatePromotionsExport($this->createMockAffiliate());

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $response);
    }

    public function testReturns200StatusCodeOnSuccess(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $this->csvResponseBody()),
        ]);

        $response = $api->getAffiliatePromotionsExport($this->createMockAffiliate());

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testResponseBodyContainsCsvContent(): void
    {
        $csvBody = $this->csvResponseBody();

        $api = $this->createApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $csvBody),
        ]);

        $response = $api->getAffiliatePromotionsExport($this->createMockAffiliate());

        $body = (string) $response->getBody();
        $this->assertStringContainsString('Program,Name,Start Date', $body);
        $this->assertStringContainsString('Campaign 1,Summer Sale', $body);
        $this->assertStringContainsString('SUMMER2024', $body);
    }

    public function testResponseIsNotWrappedInApiResponse(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $this->csvResponseBody()),
        ]);

        $response = $api->getAffiliatePromotionsExport($this->createMockAffiliate());

        $this->assertNotInstanceOf(\TPerformant\API\HTTP\ApiResponse::class, $response);
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Response::class, $response);
    }

    // --- Error Responses ---

    public function testThrowsApiExceptionOn401(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(401, [], json_encode([
                'errors' => [
                    ['error' => 'You need to sign in before continuing.'],
                ],
            ])),
        ]);

        $this->expectException(\TPerformant\API\Exception\APIException::class);
        $this->expectExceptionMessageMatches('/sign in/');

        $api->getAffiliatePromotionsExport($this->createMockAffiliate());
    }

    public function testThrowsApiExceptionOn403(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(403, [], json_encode([
                'errors' => ['You are not authorized to access this resource.'],
            ])),
        ]);

        $this->expectException(\TPerformant\API\Exception\APIException::class);
        $this->expectExceptionMessageMatches('/not authorized/');

        $api->getAffiliatePromotionsExport($this->createMockAffiliate());
    }

    public function testThrowsApiExceptionOn422WithStringErrors(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(422, [], json_encode([
                'errors' => ['Invalid filter parameters.'],
            ])),
        ]);

        $this->expectException(\TPerformant\API\Exception\APIException::class);
        $this->expectExceptionMessageMatches('/Invalid filter parameters/');

        $api->getAffiliatePromotionsExport($this->createMockAffiliate());
    }

    public function testApiExceptionContainsHttpStatusCode(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(422, [], json_encode([
                'errors' => ['Some error'],
            ])),
        ]);

        try {
            $api->getAffiliatePromotionsExport($this->createMockAffiliate());
            $this->fail('Expected APIException was not thrown');
        } catch (\TPerformant\API\Exception\APIException $e) {
            $this->assertSame(422, $e->getCode());
        }
    }

    public function testFallsBackToReasonPhraseWhenNoErrorsKeyInBody(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(400, [], json_encode(['message' => 'something went wrong'])),
        ]);

        $this->expectException(\TPerformant\API\Exception\APIException::class);
        $this->expectExceptionMessageMatches('/Bad Request/');

        $api->getAffiliatePromotionsExport($this->createMockAffiliate());
    }
}
