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
use TPerformant\API\Filter\AffiliateBannerFilter;
use TPerformant\API\Filter\AffiliateBannerSort;

class GetAffiliateBannersTest extends TestCase
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
            new Response(200, [], json_encode(['banners' => []])),
        ]);

        $api->getAffiliateBanners($this->createMockAffiliate());

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/affiliate/banners.json', $request->getUri()->getPath());
    }

    // --- Filter params ---

    public function testFilterQueryIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['banners' => []])),
        ]);

        $filter = (new AffiliateBannerFilter())->query('my banner');
        $api->getAffiliateBanners($this->createMockAffiliate(), $filter);

        $this->assertSame('my banner', $this->getQueryParams()['filter']['query']);
    }

    public function testFilterDimensionsIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['banners' => []])),
        ]);

        $filter = (new AffiliateBannerFilter())->dimensions('300x250');
        $api->getAffiliateBanners($this->createMockAffiliate(), $filter);

        $this->assertSame('300x250', $this->getQueryParams()['filter']['dimensions']);
    }

    public function testFilterCategoryIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['banners' => []])),
        ]);

        $filter = (new AffiliateBannerFilter())->category('fashion');
        $api->getAffiliateBanners($this->createMockAffiliate(), $filter);

        $this->assertSame('fashion', $this->getQueryParams()['filter']['category']);
    }

    public function testFilterFriendlyTypeIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['banners' => []])),
        ]);

        $filter = (new AffiliateBannerFilter())->friendlyType('image');
        $api->getAffiliateBanners($this->createMockAffiliate(), $filter);

        $this->assertSame('image', $this->getQueryParams()['filter']['friendly_type']);
    }

    // --- Sort params ---

    public function testSortDimensionsIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['banners' => []])),
        ]);

        $sort = (new AffiliateBannerSort())->dimensionsAsc();
        $api->getAffiliateBanners($this->createMockAffiliate(), null, $sort);

        $this->assertSame('asc', $this->getQueryParams()['sort']['dimensions']);
    }

    public function testSortCategoryIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['banners' => []])),
        ]);

        $sort = (new AffiliateBannerSort())->categoryDesc();
        $api->getAffiliateBanners($this->createMockAffiliate(), null, $sort);

        $this->assertSame('desc', $this->getQueryParams()['sort']['category']);
    }

    public function testSortFriendlyTypeIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['banners' => []])),
        ]);

        $sort = (new AffiliateBannerSort())->friendlyTypeAsc();
        $api->getAffiliateBanners($this->createMockAffiliate(), null, $sort);

        $this->assertSame('asc', $this->getQueryParams()['sort']['friendly_type']);
    }

    public function testSortClicksIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['banners' => []])),
        ]);

        $sort = (new AffiliateBannerSort())->clicksDesc();
        $api->getAffiliateBanners($this->createMockAffiliate(), null, $sort);

        $this->assertSame('desc', $this->getQueryParams()['sort']['clicks']);
    }

    public function testSortActionsIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['banners' => []])),
        ]);

        $sort = (new AffiliateBannerSort())->actionsAsc();
        $api->getAffiliateBanners($this->createMockAffiliate(), null, $sort);

        $this->assertSame('asc', $this->getQueryParams()['sort']['actions']);
    }

    public function testSortConversionRateIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['banners' => []])),
        ]);

        $sort = (new AffiliateBannerSort())->conversionRateDesc();
        $api->getAffiliateBanners($this->createMockAffiliate(), null, $sort);

        $this->assertSame('desc', $this->getQueryParams()['sort']['conversion_rate']);
    }

    // --- banner_id ---

    public function testBannerIdIsAddedAsQueryParamWhenIntegerProvided(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['banners' => []])),
        ]);

        $api->getAffiliateBanners($this->createMockAffiliate(), null, null, 99);

        $this->assertSame('99', $this->getQueryParams()['banner_id']);
    }

    public function testBannerIdIsAddedAsQueryParamWhenSlugProvided(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['banners' => []])),
        ]);

        $api->getAffiliateBanners($this->createMockAffiliate(), null, null, 'my-banner');

        $this->assertSame('my-banner', $this->getQueryParams()['banner_id']);
    }

    public function testBannerIdIsNotAddedToParamsWhenNull(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['banners' => []])),
        ]);

        $api->getAffiliateBanners($this->createMockAffiliate());

        $this->assertArrayNotHasKey('banner_id', $this->getQueryParams());
    }

    // --- Invalid banner_id ---

    public function invalidBannerIdProvider(): array
    {
        return [
            'boolean true'        => [true],
            'boolean false'       => [false],
            'array'               => [[[]]],
            'empty string'        => [''],
            'whitespace string'   => ['   '],
            'zero'                => [0],
            'negative integer'    => [-1],
            'float'               => [1.5],
            'string with slash'   => ['/foo'],
            'string with space'   => ['foo bar'],
            'string with special' => ['foo!'],
        ];
    }

    /**
     * @dataProvider invalidBannerIdProvider
     */
    public function testInvalidBannerIdThrowsExceptionAndDoesNotSendRequest($invalidId): void
    {
        $api = $this->createApiWithMockHttp([]);

        $this->expectException(TPException::class);
        $this->expectExceptionMessage(
            'Second argument of Api::getAffiliateBanners() should be interpolated safely to a string and not be boolean'
        );

        $api->getAffiliateBanners($this->createMockAffiliate(), null, null, $invalidId);
    }
}
