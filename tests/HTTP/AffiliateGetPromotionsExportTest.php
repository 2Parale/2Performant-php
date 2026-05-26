<?php

namespace TPerformant\API\Tests\HTTP;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\HTTP\Affiliate;
use TPerformant\API\HTTP\SavedSession;
use TPerformant\API\Filter\AffiliateAdvertiserPromotionFilter;
use TPerformant\API\Filter\AffiliateAdvertiserPromotionSort;

class AffiliateGetPromotionsExportTest extends TestCase
{
    private function initApiWithMockHttp(array $responses): void
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);

        Api::init('https://api.2performant.com', [
            'http' => ['handler' => $handlerStack],
        ]);
    }

    private function createAffiliate(
        string $accessToken = 'initial-token',
        string $clientToken = 'initial-client',
        string $uid = 'test@example.com'
    ): Affiliate {
        $affiliate = $this->getMockBuilder(Affiliate::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $session = new SavedSession($accessToken, $clientToken, $uid);
        $affiliate->updateAuthTokens($session);

        return $affiliate;
    }

    private function csvResponseBody(): string
    {
        return "Program,Name,Start Date,End Date,Affiliate Description,Customer Description,Coupon,Landing Page,Benefits & Tools\n"
             . "Campaign 1,Summer Sale,2024-06-01,2024-08-31,Great discounts,Exclusive deals,SUMMER2024,https://example.com/summer,Bonuses & Special Banners\n";
    }

    // --- Return Value ---

    public function testReturnsCsvAsString(): void
    {
        $csv = $this->csvResponseBody();
        $this->initApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $csv),
        ]);

        $result = $this->createAffiliate()->getPromotionsExport();

        $this->assertIsString($result);
        $this->assertSame($csv, $result);
    }

    public function testReturnedStringContainsCsvContent(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $this->csvResponseBody()),
        ]);

        $result = $this->createAffiliate()->getPromotionsExport();

        $this->assertStringContainsString('Program,Name,Start Date', $result);
        $this->assertStringContainsString('Campaign 1,Summer Sale', $result);
        $this->assertStringContainsString('SUMMER2024', $result);
    }

    // --- Auth Token Refresh ---

    public function testUpdatesAuthTokensFromResponseHeaders(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [
                'Content-Type' => 'text/csv',
                'access-token' => 'new-access-token',
                'client' => 'new-client-token',
                'uid' => 'new@example.com',
            ], $this->csvResponseBody()),
        ]);

        $affiliate = $this->createAffiliate();
        $affiliate->getPromotionsExport();

        $this->assertSame('new-access-token', $affiliate->getAccessToken());
        $this->assertSame('new-client-token', $affiliate->getClientToken());
        $this->assertSame('new@example.com', $affiliate->getUid());
    }

    public function testUpdatedAuthTokensAreStringsNotArrays(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [
                'Content-Type' => 'text/csv',
                'access-token' => 'refreshed-token',
                'client' => 'refreshed-client',
                'uid' => 'refreshed@example.com',
            ], $this->csvResponseBody()),
        ]);

        $affiliate = $this->createAffiliate();
        $affiliate->getPromotionsExport();

        $this->assertIsString($affiliate->getAccessToken());
        $this->assertIsString($affiliate->getClientToken());
        $this->assertIsString($affiliate->getUid());
    }

    public function testKeepsExistingTokensWhenResponseHasNoAuthHeaders(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $this->csvResponseBody()),
        ]);

        $affiliate = $this->createAffiliate('keep-token', 'keep-client', 'keep@example.com');
        $affiliate->getPromotionsExport();

        $this->assertSame('keep-token', $affiliate->getAccessToken());
        $this->assertSame('keep-client', $affiliate->getClientToken());
        $this->assertSame('keep@example.com', $affiliate->getUid());
    }

    // --- Filter & Sort Delegation ---

    public function testAcceptsFilterParameter(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $this->csvResponseBody()),
        ]);

        $filter = new AffiliateAdvertiserPromotionFilter();
        $filter->affrequestStatus('accepted');

        $result = $this->createAffiliate()->getPromotionsExport($filter);

        $this->assertIsString($result);
    }

    public function testAcceptsSortParameter(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $this->csvResponseBody()),
        ]);

        $sort = new AffiliateAdvertiserPromotionSort();
        $sort->promotionStartAsc();

        $result = $this->createAffiliate()->getPromotionsExport(null, $sort);

        $this->assertIsString($result);
    }

    public function testAcceptsBothFilterAndSort(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, ['Content-Type' => 'text/csv'], $this->csvResponseBody()),
        ]);

        $filter = new AffiliateAdvertiserPromotionFilter();
        $filter->affrequestStatus('accepted');

        $sort = new AffiliateAdvertiserPromotionSort();
        $sort->campaignNameDesc();

        $result = $this->createAffiliate()->getPromotionsExport($filter, $sort);

        $this->assertIsString($result);
    }

    // --- Error Propagation ---

    public function testPropagatesApiExceptionOnError(): void
    {
        $this->initApiWithMockHttp([
            new Response(401, [], json_encode([
                'errors' => [
                    ['error' => 'You need to sign in before continuing.'],
                ],
            ])),
        ]);

        $this->expectException(\TPerformant\API\Exception\APIException::class);

        $this->createAffiliate()->getPromotionsExport();
    }
}
