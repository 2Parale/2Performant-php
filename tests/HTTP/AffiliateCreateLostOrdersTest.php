<?php

namespace TPerformant\API\Tests\HTTP;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\HTTP\Affiliate;
use TPerformant\API\HTTP\SavedSession;

class AffiliateCreateLostOrdersTest extends TestCase
{
    private string $validCsvPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validCsvPath = tempnam(sys_get_temp_dir(), 'lost_orders_test_');
        $handle = fopen($this->validCsvPath, 'w');
        fputcsv($handle, ['campaign_unique', 'order_date', 'order_id', 'description', 'order_value', 'click_tag']);
        fputcsv($handle, ['camp1', '2024-01-01', 'ORD-001', 'Test order', '100.00', 'tag1']);
        fclose($handle);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->validCsvPath)) {
            unlink($this->validCsvPath);
        }

        parent::tearDown();
    }

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

    private function successResponseBody(): string
    {
        return json_encode([
            [
                'id' => '12345',
                'name' => 'source_file_name.csv',
                'url' => 'http://source_file_url.csv',
                'size' => 1,
                'unit' => 'Bytes',
                'uploadStatus' => 'finished',
                'type' => 'file',
            ]
        ]);
    }

    // --- Return Value ---

    public function testReturnsDecodedJson(): void
    {
        $this->initApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $result = $this->createAffiliate()->createLostOrders($this->validCsvPath);

        $this->assertIsArray($result);
        $this->assertSame('12345', $result[0]->id);
        $this->assertSame('finished', $result[0]->uploadStatus);
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
        $affiliate->createLostOrders($this->validCsvPath);

        $this->assertSame('new-access-token', $affiliate->getAccessToken());
        $this->assertSame('new-client-token', $affiliate->getClientToken());
        $this->assertSame('new@example.com', $affiliate->getUid());
    }

    public function testUpdatedAuthTokensAreStringsNotArrays(): void
    {
        $this->initApiWithMockHttp([
            new Response(201, [
                'access-token' => 'refreshed-token',
                'client' => 'refreshed-client',
                'uid' => 'refreshed@example.com',
            ], $this->successResponseBody()),
        ]);

        $affiliate = $this->createAffiliate();
        $affiliate->createLostOrders($this->validCsvPath);

        $this->assertIsString($affiliate->getAccessToken());
        $this->assertIsString($affiliate->getClientToken());
        $this->assertIsString($affiliate->getUid());
    }

    public function testKeepsExistingTokensWhenResponseHasNoAuthHeaders(): void
    {
        $this->initApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $affiliate = $this->createAffiliate('keep-token', 'keep-client', 'keep@example.com');
        $affiliate->createLostOrders($this->validCsvPath);

        $this->assertSame('keep-token', $affiliate->getAccessToken());
        $this->assertSame('keep-client', $affiliate->getClientToken());
        $this->assertSame('keep@example.com', $affiliate->getUid());
    }

    // --- Error Propagation ---

    public function testPropagatesApiExceptionOnError(): void
    {
        $this->initApiWithMockHttp([
            new Response(422, [], json_encode([
                'errors' => ['Failed to initiate lost orders processing.'],
            ])),
        ]);

        $this->expectException(\TPerformant\API\Exception\APIException::class);

        $this->createAffiliate()->createLostOrders($this->validCsvPath);
    }

    public function testPropagatesValidationExceptionForInvalidCsv(): void
    {
        $this->initApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->createAffiliate()->createLostOrders('/nonexistent/file.csv');
    }

    public function testThrowsApiExceptionOnInvalidJsonResponseBody(): void
    {
        $this->initApiWithMockHttp([
            new Response(201, [], 'not valid json {{{'),
        ]);

        $this->expectException(\TPerformant\API\Exception\APIException::class);
        $this->expectExceptionMessageMatches('/Failed to decode response body/');

        $this->createAffiliate()->createLostOrders($this->validCsvPath);
    }
}
