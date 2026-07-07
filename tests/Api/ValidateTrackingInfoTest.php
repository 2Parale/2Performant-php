<?php

namespace TPerformant\API\Tests\Api;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use TPerformant\API\Api;
use TPerformant\API\HTTP\Affiliate;

/**
 * Dedicated tests for the private validateTrackingInfo() method, exercised
 * through createAffiliateGoogleAdsLinkerTrackingSettings() which is the only caller.
 *
 * Focuses on edge cases beyond what CreateAffiliateGoogleAdsLinkerTrackingSettingsTest covers:
 * non-array items, non-string urls, whitespace-only urls, mixed valid/invalid lists.
 */
class ValidateTrackingInfoTest extends TestCase
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

    private function successResponseBody(): string
    {
        return json_encode([['final_url' => 'https://example.com', 'tracking_url' => 'https://event.2performant.com/...']]);
    }

    // --- Empty array ---

    public function testEmptyTrackingInfoArrayThrows(): void
    {
        $api = $this->createApiWithMockHttp([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/trackingInfo must not be empty/');

        $api->createAffiliateGoogleAdsLinkerTrackingSettings($this->createMockAffiliate(), []);
    }

    public function testEmptyArrayDoesNotSendHttpRequest(): void
    {
        $api = $this->createApiWithMockHttp([]);

        try {
            $api->createAffiliateGoogleAdsLinkerTrackingSettings($this->createMockAffiliate(), []);
        } catch (\InvalidArgumentException $e) {
            // expected
        }

        $this->assertCount(0, $this->requestHistory);
    }

    // --- Missing url key ---

    public function testItemWithoutUrlKeyThrows(): void
    {
        $api = $this->createApiWithMockHttp([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/"url"/');

        $api->createAffiliateGoogleAdsLinkerTrackingSettings($this->createMockAffiliate(), [
            ['stats_tags' => 'tag1'],
        ]);
    }

    public function testItemWithOnlyUnrelatedKeysThrows(): void
    {
        $api = $this->createApiWithMockHttp([]);

        $this->expectException(\InvalidArgumentException::class);

        $api->createAffiliateGoogleAdsLinkerTrackingSettings($this->createMockAffiliate(), [
            ['href' => 'https://example.com', 'label' => 'Shop'],
        ]);
    }

    // --- Non-string url value ---

    public function testItemWithIntegerUrlThrows(): void
    {
        $api = $this->createApiWithMockHttp([]);

        $this->expectException(\InvalidArgumentException::class);

        $api->createAffiliateGoogleAdsLinkerTrackingSettings($this->createMockAffiliate(), [
            ['url' => 42],
        ]);
    }

    public function testItemWithNullUrlThrows(): void
    {
        $api = $this->createApiWithMockHttp([]);

        $this->expectException(\InvalidArgumentException::class);

        $api->createAffiliateGoogleAdsLinkerTrackingSettings($this->createMockAffiliate(), [
            ['url' => null],
        ]);
    }

    public function testItemWithBooleanUrlThrows(): void
    {
        $api = $this->createApiWithMockHttp([]);

        $this->expectException(\InvalidArgumentException::class);

        $api->createAffiliateGoogleAdsLinkerTrackingSettings($this->createMockAffiliate(), [
            ['url' => true],
        ]);
    }

    // --- Empty url string ---

    public function testItemWithEmptyUrlStringThrows(): void
    {
        $api = $this->createApiWithMockHttp([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/"url"/');

        $api->createAffiliateGoogleAdsLinkerTrackingSettings($this->createMockAffiliate(), [
            ['url' => ''],
        ]);
    }

    public function testItemWithWhitespaceOnlyUrlThrows(): void
    {
        $api = $this->createApiWithMockHttp([]);

        $this->expectException(\InvalidArgumentException::class);

        $api->createAffiliateGoogleAdsLinkerTrackingSettings($this->createMockAffiliate(), [
            ['url' => '   '],
        ]);
    }

    // --- Non-array item ---

    public function testStringItemInsteadOfArrayThrows(): void
    {
        $api = $this->createApiWithMockHttp([]);

        $this->expectException(\InvalidArgumentException::class);

        $api->createAffiliateGoogleAdsLinkerTrackingSettings($this->createMockAffiliate(), [
            'https://example.com',
        ]);
    }

    public function testNullItemInsteadOfArrayThrows(): void
    {
        $api = $this->createApiWithMockHttp([]);

        $this->expectException(\InvalidArgumentException::class);

        $api->createAffiliateGoogleAdsLinkerTrackingSettings($this->createMockAffiliate(), [
            null,
        ]);
    }

    // --- Mixed valid/invalid list ---

    public function testListWithOneInvalidItemAmongValidOnesThrows(): void
    {
        $api = $this->createApiWithMockHttp([]);

        $this->expectException(\InvalidArgumentException::class);

        $api->createAffiliateGoogleAdsLinkerTrackingSettings($this->createMockAffiliate(), [
            ['url' => 'https://example.com/valid'],
            ['url' => ''],   // invalid: empty url
        ]);
    }

    // --- Valid items ---

    public function testSingleValidItemDoesNotThrow(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], $this->successResponseBody()),
        ]);

        $response = $api->createAffiliateGoogleAdsLinkerTrackingSettings($this->createMockAffiliate(), [
            ['url' => 'https://example.com'],
        ]);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testMultipleValidItemsDoNotThrow(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], $this->successResponseBody()),
        ]);

        $response = $api->createAffiliateGoogleAdsLinkerTrackingSettings($this->createMockAffiliate(), [
            ['url' => 'https://example.com/page1'],
            ['url' => 'https://example.com/page2', 'stats_tags' => 'tag1,tag2'],
        ]);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testItemWithUrlAndOptionalStatsTagsIsValid(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], $this->successResponseBody()),
        ]);

        $response = $api->createAffiliateGoogleAdsLinkerTrackingSettings($this->createMockAffiliate(), [
            ['url' => 'https://example.com', 'stats_tags' => 'campaign=summer'],
        ]);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testValidItemsSendHttpRequest(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], $this->successResponseBody()),
        ]);

        $api->createAffiliateGoogleAdsLinkerTrackingSettings($this->createMockAffiliate(), [
            ['url' => 'https://example.com'],
        ]);

        $this->assertCount(1, $this->requestHistory);
    }
}
