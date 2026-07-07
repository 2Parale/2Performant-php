<?php

namespace TPerformant\API\Tests\Api;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\HTTP\Affiliate;
use TPerformant\API\Filter\AffiliateProductFeedFilter;
use TPerformant\API\Filter\AffiliateProductFeedSort;

class GetAffiliateProductFeedsTest extends TestCase
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
            new Response(200, [], json_encode(['product_feeds' => []])),
        ]);

        $api->getAffiliateProductFeeds($this->createMockAffiliate());

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/affiliate/product_feeds.json', $request->getUri()->getPath());
    }

    public function testSendsAuthHeaders(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['product_feeds' => []])),
        ]);

        $api->getAffiliateProductFeeds($this->createMockAffiliate());

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('test-access-token', $request->getHeaderLine('access-token'));
        $this->assertSame('test-client-token', $request->getHeaderLine('client'));
        $this->assertSame('test@example.com', $request->getHeaderLine('uid'));
    }

    public function testSendsNoQueryParamsWithoutFilterOrSort(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['product_feeds' => []])),
        ]);

        $api->getAffiliateProductFeeds($this->createMockAffiliate());

        $this->assertEmpty($this->requestHistory[0]['request']->getUri()->getQuery());
    }

    // --- Filter params ---

    public function testFilterProgramIdIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['product_feeds' => []])),
        ]);

        $filter = (new AffiliateProductFeedFilter())->programId(42);
        $api->getAffiliateProductFeeds($this->createMockAffiliate(), $filter);

        $this->assertSame('42', $this->getQueryParams()['filter']['program_id']);
    }

    public function testPaginationParamsAreSentThroughFilter(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['product_feeds' => []])),
        ]);

        $filter = (new AffiliateProductFeedFilter())->page(2)->perpage(25);
        $api->getAffiliateProductFeeds($this->createMockAffiliate(), $filter);

        $params = $this->getQueryParams();
        $this->assertSame('2', $params['page']);
        $this->assertSame('25', $params['perpage']);
    }

    // --- Sort params ---

    public function testPassingEmptySortObjectDoesNotAddSortQueryParams(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['product_feeds' => []])),
        ]);

        $sort = new AffiliateProductFeedSort();
        $api->getAffiliateProductFeeds($this->createMockAffiliate(), null, $sort);

        $params = $this->getQueryParams();
        // AffiliateProductFeedSort has no sortable fields, so sort key should be empty
        $this->assertTrue(empty($params['sort'] ?? []));
    }

    // --- Filter and sort merged ---

    public function testFilterAndSortParamsAreMergedWithoutConflict(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['product_feeds' => []])),
        ]);

        $filter = (new AffiliateProductFeedFilter())->programId(7);
        $sort = new AffiliateProductFeedSort();

        $api->getAffiliateProductFeeds($this->createMockAffiliate(), $filter, $sort);

        $params = $this->getQueryParams();
        $this->assertSame('7', $params['filter']['program_id']);
    }
}
