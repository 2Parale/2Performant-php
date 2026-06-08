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

class GetAffiliatePromotionsTest extends TestCase
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

        return $affiliate;
    }

    private function getQueryParams(): array
    {
        parse_str($this->requestHistory[0]['request']->getUri()->getQuery(), $params);
        return $params;
    }

    // --- Request ---

    public function testSendsGetRequestToCorrectUrl(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['advertiser_promotions' => []])),
        ]);

        $api->getAffiliatePromotions($this->createMockAffiliate());

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/affiliate/advertiser_promotions.json', $request->getUri()->getPath());
    }

    // --- Filter params ---

    public function testFilterAffrequestStatusIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['advertiser_promotions' => []])),
        ]);

        $filter = (new AffiliateAdvertiserPromotionFilter())->affrequestStatus('accepted');
        $api->getAffiliatePromotions($this->createMockAffiliate(), $filter);

        $this->assertSame('accepted', $this->getQueryParams()['filter']['affrequest_status']);
    }

    public function testFilterCouponIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['advertiser_promotions' => []])),
        ]);

        $filter = (new AffiliateAdvertiserPromotionFilter())->coupon('SAVE10');
        $api->getAffiliatePromotions($this->createMockAffiliate(), $filter);

        $this->assertSame('SAVE10', $this->getQueryParams()['filter']['coupon']);
    }

    // --- Sort params ---

    public function testSortPromotionStartIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['advertiser_promotions' => []])),
        ]);

        $sort = (new AffiliateAdvertiserPromotionSort())->promotionStartAsc();
        $api->getAffiliatePromotions($this->createMockAffiliate(), null, $sort);

        $this->assertSame('asc', $this->getQueryParams()['sort']['promotion_start']);
    }

    public function testSortPromotionEndIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['advertiser_promotions' => []])),
        ]);

        $sort = (new AffiliateAdvertiserPromotionSort())->promotionEndDesc();
        $api->getAffiliatePromotions($this->createMockAffiliate(), null, $sort);

        $this->assertSame('desc', $this->getQueryParams()['sort']['promotion_end']);
    }

    public function testSortCampaignNameIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['advertiser_promotions' => []])),
        ]);

        $sort = (new AffiliateAdvertiserPromotionSort())->campaignNameAsc();
        $api->getAffiliatePromotions($this->createMockAffiliate(), null, $sort);

        $this->assertSame('asc', $this->getQueryParams()['sort']['campaign_name']);
    }
}