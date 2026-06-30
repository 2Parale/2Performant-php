<?php

namespace TPerformant\API\Tests\HTTP;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\HTTP\Affiliate;
use TPerformant\API\HTTP\SavedSession;

class AffiliateCreateGoogleAdsLinkerTrackingSettingsTest extends TestCase
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

    private function sampleTrackingInfo(): array
    {
        return [
            ['url' => 'url1.com'],
            ['url' => 'url2.com', 'stats_tags' => 'tag1, tag2'],
        ];
    }

    private function successResponseBody(): string
    {
        return json_encode([
            [
                'final_url' => 'url1.com',
                'tracking_url' => 'https://b-event.2performant.com/events/click?ad_type=quicklink',
                'url_suffix' => '2pau=e5442c1ed&2ptt=quicklink',
            ],
        ]);
    }

    // --- Return Value ---

    public function testReturnsDecodedJson(): void
    {
        $this->initApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $result = $this->createAffiliate()->createGoogleAdsLinkerTrackingSettings($this->sampleTrackingInfo());

        $this->assertIsArray($result);
        $this->assertSame('url1.com', $result[0]->final_url);
        $this->assertStringContainsString('b-event.2performant.com', $result[0]->tracking_url);
    }

    // --- Auth Token Refresh ---

    public function testUpdatesAuthTokensFromResponseHeaders(): void
    {
        $this->initApiWithMockHttp([
            new Response(201, [
                'access-token' => 'new-access-token',
                'client' => 'new-client-token',
                'uid' => 'new@example.com',
            ], $this->successResponseBody()),
        ]);

        $affiliate = $this->createAffiliate();
        $affiliate->createGoogleAdsLinkerTrackingSettings($this->sampleTrackingInfo());

        $this->assertSame('new-access-token', $affiliate->getAccessToken());
        $this->assertSame('new-client-token', $affiliate->getClientToken());
        $this->assertSame('new@example.com', $affiliate->getUid());
    }

    public function testKeepsExistingTokensWhenResponseHasNoAuthHeaders(): void
    {
        $this->initApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $affiliate = $this->createAffiliate('keep-token', 'keep-client', 'keep@example.com');
        $affiliate->createGoogleAdsLinkerTrackingSettings($this->sampleTrackingInfo());

        $this->assertSame('keep-token', $affiliate->getAccessToken());
        $this->assertSame('keep-client', $affiliate->getClientToken());
        $this->assertSame('keep@example.com', $affiliate->getUid());
    }

    // --- Error Propagation ---

    public function testPropagatesApiExceptionOnError(): void
    {
        $this->initApiWithMockHttp([
            new Response(400, [], json_encode([
                'errors' => [
                    ['title' => 'Invalid tracking info'],
                ],
            ])),
        ]);

        $this->expectException(\TPerformant\API\Exception\APIException::class);

        $this->createAffiliate()->createGoogleAdsLinkerTrackingSettings($this->sampleTrackingInfo());
    }

    public function testThrowsApiExceptionOnInvalidJsonResponseBody(): void
    {
        $this->initApiWithMockHttp([
            new Response(201, [], 'not valid json {{{'),
        ]);

        $this->expectException(\TPerformant\API\Exception\APIException::class);
        $this->expectExceptionMessageMatches('/Failed to decode response body/');

        $this->createAffiliate()->createGoogleAdsLinkerTrackingSettings($this->sampleTrackingInfo());
    }
}
