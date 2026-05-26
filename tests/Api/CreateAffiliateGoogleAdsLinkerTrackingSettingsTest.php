<?php

namespace TPerformant\API\Tests\Api;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\HTTP\Affiliate;

class CreateAffiliateGoogleAdsLinkerTrackingSettingsTest extends TestCase
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
                'tracking_url' => 'https://b-event.2performant.com/events/click?ad_type=quicklink&aff_code=e5442c1ed&unique=184f69294&redirect_to={lpurl}&st={gclid}%2Ctag1%2C%20tag2',
                'url_suffix' => '2pau=e5442c1ed&2ptt=quicklink&2ptu=184f69294&2pdlst={gclid}&utm_source=2parale&utm_medium=quicklink&utm_campaign=e5442c1ed',
            ],
            [
                'final_url' => 'url2.com',
                'tracking_url' => 'https://b-event.2performant.com/events/click?ad_type=quicklink&aff_code=e5442c1ed&unique=184f69294&redirect_to={lpurl}&st={gclid}%2Ctag1%2C%20tag2',
                'url_suffix' => '2pau=e5442c1ed&2ptt=quicklink&2ptu=184f69294&2pdlst={gclid}&utm_source=2parale&utm_medium=quicklink&utm_campaign=e5442c1ed',
            ],
        ]);
    }

    // --- HTTP Request ---

    public function testSendsPostRequest(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $api->createAffiliateGoogleAdsLinkerTrackingSettings(
            $this->createMockAffiliate(),
            $this->sampleTrackingInfo()
        );

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('POST', $request->getMethod());
    }

    public function testSendsToCorrectEndpoint(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $api->createAffiliateGoogleAdsLinkerTrackingSettings(
            $this->createMockAffiliate(),
            $this->sampleTrackingInfo()
        );

        $request = $this->requestHistory[0]['request'];
        $this->assertSame(
            '/affiliate/google_ads_linker/tracking_settings.json',
            $request->getUri()->getPath()
        );
    }

    public function testSendsAuthHeaders(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $api->createAffiliateGoogleAdsLinkerTrackingSettings(
            $this->createMockAffiliate(),
            $this->sampleTrackingInfo()
        );

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('test-access-token', $request->getHeaderLine('access-token'));
        $this->assertSame('test-client-token', $request->getHeaderLine('client'));
        $this->assertSame('test@example.com', $request->getHeaderLine('uid'));
    }

    public function testSendsJsonContentType(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $api->createAffiliateGoogleAdsLinkerTrackingSettings(
            $this->createMockAffiliate(),
            $this->sampleTrackingInfo()
        );

        $request = $this->requestHistory[0]['request'];
        $this->assertStringContainsString('application/json', $request->getHeaderLine('Content-Type'));
    }

    public function testSendsCorrectRequestBody(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $api->createAffiliateGoogleAdsLinkerTrackingSettings(
            $this->createMockAffiliate(),
            $this->sampleTrackingInfo()
        );

        $request = $this->requestHistory[0]['request'];
        $body = json_decode($request->getBody()->getContents(), true);

        $this->assertSame([
            'tracking_info' => [
                ['url' => 'url1.com'],
                ['url' => 'url2.com', 'stats_tags' => 'tag1, tag2'],
            ],
        ], $body);
    }

    // --- Success Response ---

    public function testReturnsPsr7ResponseOnSuccess(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $response = $api->createAffiliateGoogleAdsLinkerTrackingSettings(
            $this->createMockAffiliate(),
            $this->sampleTrackingInfo()
        );

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $response);
    }

    public function testReturns201StatusCodeOnSuccess(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $response = $api->createAffiliateGoogleAdsLinkerTrackingSettings(
            $this->createMockAffiliate(),
            $this->sampleTrackingInfo()
        );

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testResponseBodyContainsTrackingSettings(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $response = $api->createAffiliateGoogleAdsLinkerTrackingSettings(
            $this->createMockAffiliate(),
            $this->sampleTrackingInfo()
        );

        $data = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertSame('url1.com', $data[0]['final_url']);
        $this->assertStringContainsString('b-event.2performant.com', $data[0]['tracking_url']);
        $this->assertStringContainsString('2pau=e5442c1ed', $data[0]['url_suffix']);
    }

    // --- Error Responses ---

    public function testThrowsApiExceptionOn400WithStructuredErrors(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(400, [], json_encode([
                'errors' => [
                    ['title' => 'Invalid tracking info'],
                ],
            ])),
        ]);

        $this->expectException(\TPerformant\API\Exception\APIException::class);
        $this->expectExceptionMessageMatches('/Invalid tracking info/');

        $api->createAffiliateGoogleAdsLinkerTrackingSettings(
            $this->createMockAffiliate(),
            $this->sampleTrackingInfo()
        );
    }

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

        $api->createAffiliateGoogleAdsLinkerTrackingSettings(
            $this->createMockAffiliate(),
            $this->sampleTrackingInfo()
        );
    }

    public function testThrowsApiExceptionOn409WithDetails(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(409, [], json_encode([
                'errors' => [
                    [
                        'title' => 'Conflict',
                        'details' => 'Tracking settings already exist',
                    ],
                ],
            ])),
        ]);

        $this->expectException(\TPerformant\API\Exception\APIException::class);
        $this->expectExceptionMessageMatches('/Conflict - Tracking settings already exist/');

        $api->createAffiliateGoogleAdsLinkerTrackingSettings(
            $this->createMockAffiliate(),
            $this->sampleTrackingInfo()
        );
    }

    public function testApiExceptionContainsHttpStatusCode(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(409, [], json_encode([
                'errors' => [
                    ['title' => 'Conflict'],
                ],
            ])),
        ]);

        try {
            $api->createAffiliateGoogleAdsLinkerTrackingSettings(
                $this->createMockAffiliate(),
                $this->sampleTrackingInfo()
            );
            $this->fail('Expected APIException was not thrown');
        } catch (\TPerformant\API\Exception\APIException $e) {
            $this->assertSame(409, $e->getCode());
        }
    }
}
