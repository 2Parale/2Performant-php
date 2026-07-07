<?php

namespace TPerformant\API\Tests\Api;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\HTTP\Advertiser;
use TPerformant\API\Filter\AdvertiserProgramFilter;
use TPerformant\API\Filter\AdvertiserProgramSort;

class GetAdvertiserProgramsTest extends TestCase
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

        return $advertiser;
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
            new Response(200, [], json_encode(['programs' => []])),
        ]);

        $api->getAdvertiserPrograms($this->createMockAdvertiser());

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/advertiser/programs.json', $request->getUri()->getPath());
    }

    public function testSendsAuthHeaders(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['programs' => []])),
        ]);

        $api->getAdvertiserPrograms($this->createMockAdvertiser());

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('test-access-token', $request->getHeaderLine('access-token'));
        $this->assertSame('test-client-token', $request->getHeaderLine('client'));
        $this->assertSame('test@example.com', $request->getHeaderLine('uid'));
    }

    public function testSendsNoQueryParamsWithoutFilterOrSort(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['programs' => []])),
        ]);

        $api->getAdvertiserPrograms($this->createMockAdvertiser());

        $this->assertEmpty($this->requestHistory[0]['request']->getUri()->getQuery());
    }

    // --- Filter params ---

    public function testFilterQueryIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['programs' => []])),
        ]);

        $filter = (new AdvertiserProgramFilter())->query('test program');
        $api->getAdvertiserPrograms($this->createMockAdvertiser(), $filter);

        $this->assertSame('test program', $this->getQueryParams()['filter']['query']);
    }

    public function testFilterCategoryIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['programs' => []])),
        ]);

        $filter = (new AdvertiserProgramFilter())->category('electronics');
        $api->getAdvertiserPrograms($this->createMockAdvertiser(), $filter);

        $this->assertSame('electronics', $this->getQueryParams()['filter']['category']);
    }

    public function testFilterCountryIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['programs' => []])),
        ]);

        $filter = (new AdvertiserProgramFilter())->country('RO');
        $api->getAdvertiserPrograms($this->createMockAdvertiser(), $filter);

        $this->assertSame('RO', $this->getQueryParams()['filter']['country']);
    }

    // --- Sort params ---

    public function testSortApprovedCommissionCountIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['programs' => []])),
        ]);

        $sort = (new AdvertiserProgramSort())->approvedCommissionCountAsc();
        $api->getAdvertiserPrograms($this->createMockAdvertiser(), null, $sort);

        $this->assertSame('asc', $this->getQueryParams()['sort']['approved_commission_count']);
    }

    public function testSortClickCountIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['programs' => []])),
        ]);

        $sort = (new AdvertiserProgramSort())->clickCountDesc();
        $api->getAdvertiserPrograms($this->createMockAdvertiser(), null, $sort);

        $this->assertSame('desc', $this->getQueryParams()['sort']['click_count']);
    }

    public function testSortEpcIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['programs' => []])),
        ]);

        $sort = (new AdvertiserProgramSort())->epcAsc();
        $api->getAdvertiserPrograms($this->createMockAdvertiser(), null, $sort);

        $this->assertSame('asc', $this->getQueryParams()['sort']['epc']);
    }

    // --- Filter and sort merged ---

    public function testFilterAndSortParamsAreMergedTogether(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['programs' => []])),
        ]);

        $filter = (new AdvertiserProgramFilter())->query('shoes')->country('RO');
        $sort = (new AdvertiserProgramSort())->epcDesc();

        $api->getAdvertiserPrograms($this->createMockAdvertiser(), $filter, $sort);

        $params = $this->getQueryParams();
        $this->assertSame('shoes', $params['filter']['query']);
        $this->assertSame('RO', $params['filter']['country']);
        $this->assertSame('desc', $params['sort']['epc']);
    }
}
