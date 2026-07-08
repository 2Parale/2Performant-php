<?php

namespace TPerformant\API\Tests\Api;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\HTTP\Affiliate;

/**
 * Dedicated tests for the private validateCsvFile() method, exercised through
 * createAffiliateLostOrders() which is the only caller.
 *
 * Focuses on header trimming, extra-column tolerance, and BOM behaviour.
 */
class ValidateCsvFileTest extends TestCase
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
        return json_encode([['id' => '1', 'uploadStatus' => 'finished']]);
    }

    private function createTempCsvFromHeaders(array $headers): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv_validate_test_');
        $handle = fopen($path, 'w');
        fputcsv($handle, $headers);
        fputcsv($handle, array_fill(0, count($headers), 'data'));
        fclose($handle);
        return $path;
    }

    private function createTempCsvFromRawContent(string $rawContent): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv_validate_test_');
        file_put_contents($path, $rawContent);
        return $path;
    }

    // --- Whitespace around headers trimmed ---

    public function testHeadersWithLeadingWhitespaceAreAccepted(): void
    {
        $path = $this->createTempCsvFromRawContent(
            " campaign_unique , order_date , order_id , description , order_value , click_tag \n"
            . "camp1,2024-01-01,ORD1,desc,100,tag\n"
        );

        try {
            $api = $this->createApiWithMockHttp([
                new Response(201, [], $this->successResponseBody()),
            ]);

            $response = $api->createAffiliateLostOrders($this->createMockAffiliate(), $path);

            $this->assertSame(201, $response->getStatusCode());
        } finally {
            unlink($path);
        }
    }

    public function testHeadersWithTrailingWhitespaceAreAccepted(): void
    {
        $path = $this->createTempCsvFromRawContent(
            "campaign_unique ,order_date ,order_id ,description ,order_value ,click_tag \n"
            . "camp1,2024-01-01,ORD1,desc,100,tag\n"
        );

        try {
            $api = $this->createApiWithMockHttp([
                new Response(201, [], $this->successResponseBody()),
            ]);

            $response = $api->createAffiliateLostOrders($this->createMockAffiliate(), $path);

            $this->assertSame(201, $response->getStatusCode());
        } finally {
            unlink($path);
        }
    }

    public function testWhitespacePaddedHeadersDoNotPreventHttpRequest(): void
    {
        // Whitespace-padded headers that happen to still contain a required
        // name — trimming should resolve them; no request should be missing.
        $path = $this->createTempCsvFromRawContent(
            "  campaign_unique  ,  order_date  ,  order_id  ,  description  ,  order_value  ,  click_tag  \n"
        );

        try {
            $api = $this->createApiWithMockHttp([
                new Response(201, [], $this->successResponseBody()),
            ]);

            $api->createAffiliateLostOrders($this->createMockAffiliate(), $path);

            $this->assertCount(1, $this->requestHistory);
        } finally {
            unlink($path);
        }
    }

    // --- Extra columns allowed ---

    public function testMultipleExtraColumnsDoNotFailValidation(): void
    {
        $path = $this->createTempCsvFromHeaders([
            'campaign_unique', 'order_date', 'order_id',
            'description', 'order_value', 'click_tag',
            'extra_col_1', 'extra_col_2', 'extra_col_3',
        ]);

        try {
            $api = $this->createApiWithMockHttp([
                new Response(201, [], $this->successResponseBody()),
            ]);

            $response = $api->createAffiliateLostOrders($this->createMockAffiliate(), $path);

            $this->assertSame(201, $response->getStatusCode());
        } finally {
            unlink($path);
        }
    }

    // --- BOM handling ---

    public function testUtf8BomPrefixCausesValidationFailure(): void
    {
        // PHP's trim() does not strip UTF-8 BOM bytes (\xEF\xBB\xBF), so the
        // first CSV header becomes "\xEF\xBB\xBFcampaign_unique" which does
        // not match the expected "campaign_unique" — the file is rejected.
        // This test documents the current behaviour.
        $bom = "\xEF\xBB\xBF";
        $path = $this->createTempCsvFromRawContent(
            $bom . "campaign_unique,order_date,order_id,description,order_value,click_tag\n"
            . "camp1,2024-01-01,ORD1,desc,100,tag\n"
        );

        try {
            $api = $this->createApiWithMockHttp([]);

            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessageMatches('/CSV is missing required headers/');

            $api->createAffiliateLostOrders($this->createMockAffiliate(), $path);
        } finally {
            unlink($path);
        }
    }

    public function testUtf8BomPrefixDoesNotSendHttpRequest(): void
    {
        $bom = "\xEF\xBB\xBF";
        $path = $this->createTempCsvFromRawContent(
            $bom . "campaign_unique,order_date,order_id,description,order_value,click_tag\n"
        );

        try {
            $api = $this->createApiWithMockHttp([]);

            try {
                $api->createAffiliateLostOrders($this->createMockAffiliate(), $path);
            } catch (\InvalidArgumentException $e) {
                // expected
            }

            $this->assertCount(0, $this->requestHistory);
        } finally {
            unlink($path);
        }
    }
}
