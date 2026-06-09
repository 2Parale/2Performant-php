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
use TPerformant\API\Filter\AffiliateCommissionFilter;
use TPerformant\API\Filter\AffiliateCommissionSort;

class GetAffiliateCommissionsTest extends TestCase
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
            new Response(200, [], json_encode(['commissions' => []])),
        ]);

        $api->getAffiliateCommissions($this->createMockAffiliate());

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/affiliate/commissions.json', $request->getUri()->getPath());
    }

    // --- Filter params ---

    public function testFilterQueryIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commissions' => []])),
        ]);

        $filter = (new AffiliateCommissionFilter())->query('some text');
        $api->getAffiliateCommissions($this->createMockAffiliate(), $filter);

        $this->assertSame('some text', $this->getQueryParams()['filter']['query']);
    }

    public function testFilterStatusIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commissions' => []])),
        ]);

        $filter = (new AffiliateCommissionFilter())->status('accepted');
        $api->getAffiliateCommissions($this->createMockAffiliate(), $filter);

        $this->assertSame('accepted', $this->getQueryParams()['filter']['status']);
    }

    public function testFilterDateIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commissions' => []])),
        ]);

        $filter = (new AffiliateCommissionFilter())->date('202506');
        $api->getAffiliateCommissions($this->createMockAffiliate(), $filter);

        $this->assertSame('202506', $this->getQueryParams()['filter']['date']);
    }

    public function testFilterStartDateIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commissions' => []])),
        ]);

        $filter = (new AffiliateCommissionFilter())->startDate('2025-01-01');
        $api->getAffiliateCommissions($this->createMockAffiliate(), $filter);

        $this->assertSame('2025-01-01', $this->getQueryParams()['filter']['start_date']);
    }

    public function testFilterEndDateIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commissions' => []])),
        ]);

        $filter = (new AffiliateCommissionFilter())->endDate('2025-12-31');
        $api->getAffiliateCommissions($this->createMockAffiliate(), $filter);

        $this->assertSame('2025-12-31', $this->getQueryParams()['filter']['end_date']);
    }

    public function testFilterTypeIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commissions' => []])),
        ]);

        $filter = (new AffiliateCommissionFilter())->type('sale');
        $api->getAffiliateCommissions($this->createMockAffiliate(), $filter);

        $this->assertSame('sale', $this->getQueryParams()['filter']['type']);
    }

    // --- Sort params ---

    public function testSortTransactionIdIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commissions' => []])),
        ]);

        $sort = (new AffiliateCommissionSort())->transactionIdAsc();
        $api->getAffiliateCommissions($this->createMockAffiliate(), null, $sort);

        $this->assertSame('asc', $this->getQueryParams()['sort']['transaction_id']);
    }

    public function testSortTypeIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commissions' => []])),
        ]);

        $sort = (new AffiliateCommissionSort())->typeDesc();
        $api->getAffiliateCommissions($this->createMockAffiliate(), null, $sort);

        $this->assertSame('desc', $this->getQueryParams()['sort']['type']);
    }

    public function testSortDateIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commissions' => []])),
        ]);

        $sort = (new AffiliateCommissionSort())->dateAsc();
        $api->getAffiliateCommissions($this->createMockAffiliate(), null, $sort);

        $this->assertSame('asc', $this->getQueryParams()['sort']['date']);
    }

    public function testSortCommissionIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commissions' => []])),
        ]);

        $sort = (new AffiliateCommissionSort())->commissionDesc();
        $api->getAffiliateCommissions($this->createMockAffiliate(), null, $sort);

        $this->assertSame('desc', $this->getQueryParams()['sort']['commission']);
    }

    public function testSortSaleIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commissions' => []])),
        ]);

        $sort = (new AffiliateCommissionSort())->saleAsc();
        $api->getAffiliateCommissions($this->createMockAffiliate(), null, $sort);

        $this->assertSame('asc', $this->getQueryParams()['sort']['sale']);
    }

    public function testSortUsernameIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commissions' => []])),
        ]);

        $sort = (new AffiliateCommissionSort())->usernameDesc();
        $api->getAffiliateCommissions($this->createMockAffiliate(), null, $sort);

        $this->assertSame('desc', $this->getQueryParams()['sort']['username']);
    }

    public function testSortUpdatedIsSentAsQueryParam(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commissions' => []])),
        ]);

        $sort = (new AffiliateCommissionSort())->updatedAsc();
        $api->getAffiliateCommissions($this->createMockAffiliate(), null, $sort);

        $this->assertSame('asc', $this->getQueryParams()['sort']['updated']);
    }

    // --- program_id ---

    public function testProgramIdIsAddedAsQueryParamWhenIntegerProvided(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commissions' => []])),
        ]);

        $api->getAffiliateCommissions($this->createMockAffiliate(), null, null, 42);

        $this->assertSame('42', $this->getQueryParams()['program_id']);
    }

    public function testProgramIdIsAddedAsQueryParamWhenSlugProvided(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commissions' => []])),
        ]);

        $api->getAffiliateCommissions($this->createMockAffiliate(), null, null, 'my-program');

        $this->assertSame('my-program', $this->getQueryParams()['program_id']);
    }

    public function testProgramIdIsNotAddedToParamsWhenNull(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['commissions' => []])),
        ]);

        $api->getAffiliateCommissions($this->createMockAffiliate());

        $this->assertArrayNotHasKey('program_id', $this->getQueryParams());
    }

    // --- Invalid program_id ---

    public function invalidProgramIdProvider(): array
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
     * @dataProvider invalidProgramIdProvider
     */
    public function testInvalidProgramIdThrowsExceptionAndDoesNotSendRequest($invalidId): void
    {
        // No responses queued: if a request were made, MockHandler would throw
        // a different exception, causing the test to fail — validating that no
        // HTTP call reaches the wire when the id is invalid.
        $api = $this->createApiWithMockHttp([]);

        $this->expectException(TPException::class);
        $this->expectExceptionMessage(
            'Parameter id passed to Api::getAffiliateCommissions() must be a positive integer or an alphanumeric slug.'
        );

        $api->getAffiliateCommissions($this->createMockAffiliate(), null, null, $invalidId);
    }
}
