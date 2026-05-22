<?php

namespace TPerformant\API\Tests\Api;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\HTTP\Affiliate;

class CreateAffiliateLostOrdersTest extends TestCase
{
    private array $requestHistory = [];
    private string $validCsvPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestHistory = [];

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

    private function createApiWithMockHttp(array $responses): Api
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
    
        $handlerStack->push(function (callable $handler) {
            return function (\Psr\Http\Message\RequestInterface $request, array $options) use ($handler) {
                $contents = $request->getBody()->getContents();
                $request = $request->withBody(\GuzzleHttp\Psr7\Utils::streamFor($contents));
                return $handler($request, $options);
            };
        }, 'buffer_body');
    
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

    private function createTempCsv(array $headers): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv_test_');
        $handle = fopen($path, 'w');
        fputcsv($handle, $headers);
        fclose($handle);

        return $path;
    }

    // --- CSV Validation ---

    public function testThrowsExceptionForNonExistentFile(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/File not found or not readable/');

        $api->createAffiliateLostOrders($this->createMockAffiliate(), '/nonexistent/path/to/file.csv');
    }

    public function testThrowsExceptionForEmptyFile(): void
    {
        $emptyPath = tempnam(sys_get_temp_dir(), 'empty_csv_');

        try {
            $api = $this->createApiWithMockHttp([
                new Response(201, [], $this->successResponseBody()),
            ]);

            $this->expectException(\InvalidArgumentException::class);

            $api->createAffiliateLostOrders($this->createMockAffiliate(), $emptyPath);
        } finally {
            unlink($emptyPath);
        }
    }

    public function testThrowsExceptionForMissingRequiredHeaders(): void
    {
        $csvPath = $this->createTempCsv(['campaign_unique', 'order_date']);

        try {
            $api = $this->createApiWithMockHttp([
                new Response(201, [], $this->successResponseBody()),
            ]);

            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessageMatches('/CSV is missing required headers/');

            $api->createAffiliateLostOrders($this->createMockAffiliate(), $csvPath);
        } finally {
            unlink($csvPath);
        }
    }

    public function testMissingHeadersExceptionListsAllAbsentColumns(): void
    {
        $csvPath = $this->createTempCsv(['campaign_unique']);

        try {
            $api = $this->createApiWithMockHttp([
                new Response(201, [], $this->successResponseBody()),
            ]);

            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessageMatches('/order_date/');

            $api->createAffiliateLostOrders($this->createMockAffiliate(), $csvPath);
        } finally {
            unlink($csvPath);
        }
    }

    public function testValidationDoesNotRejectExtraHeaders(): void
    {
        $csvPath = $this->createTempCsv([
            'campaign_unique', 'order_date', 'order_id',
            'description', 'order_value', 'click_tag', 'extra_column',
        ]);

        try {
            $api = $this->createApiWithMockHttp([
                new Response(201, [], $this->successResponseBody()),
            ]);

            $response = $api->createAffiliateLostOrders($this->createMockAffiliate(), $csvPath);

            $this->assertSame(201, $response->getStatusCode());
        } finally {
            unlink($csvPath);
        }
    }

    public function testValidationDoesNotMakeHttpRequest(): void
    {
        $api = $this->createApiWithMockHttp([]);

        try {
            $api->createAffiliateLostOrders($this->createMockAffiliate(), '/nonexistent/file.csv');
        } catch (\InvalidArgumentException $e) {
            // expected
        }

        $this->assertCount(0, $this->requestHistory);
    }

    // --- HTTP Request ---

    public function testSendsPostRequest(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $api->createAffiliateLostOrders($this->createMockAffiliate(), $this->validCsvPath);

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('POST', $request->getMethod());
    }

    public function testSendsToCorrectEndpoint(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $api->createAffiliateLostOrders($this->createMockAffiliate(), $this->validCsvPath);

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('/affiliate/lost_orders.json', $request->getUri()->getPath());
    }

    public function testSendsAuthHeaders(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $api->createAffiliateLostOrders($this->createMockAffiliate(), $this->validCsvPath);

        $request = $this->requestHistory[0]['request'];
        $this->assertSame('test-access-token', $request->getHeaderLine('access-token'));
        $this->assertSame('test-client-token', $request->getHeaderLine('client'));
        $this->assertSame('test@example.com', $request->getHeaderLine('uid'));
    }

    public function testSendsMultipartContentType(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $api->createAffiliateLostOrders($this->createMockAffiliate(), $this->validCsvPath);

        $request = $this->requestHistory[0]['request'];
        $contentType = $request->getHeaderLine('Content-Type');
        $this->assertStringContainsString('multipart/form-data', $contentType);
        $this->assertStringContainsString('boundary=', $contentType);
    }

    public function testBodyContainsSourceFileField(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $api->createAffiliateLostOrders($this->createMockAffiliate(), $this->validCsvPath);

        $request = $this->requestHistory[0]['request'];
        $body = (string) $request->getBody();
        $this->assertStringContainsString('name="source_file"', $body);
    }

    public function testBodyContainsFilename(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $api->createAffiliateLostOrders($this->createMockAffiliate(), $this->validCsvPath);

        $request = $this->requestHistory[0]['request'];
        $body = (string) $request->getBody();
        $this->assertStringContainsString('filename="' . basename($this->validCsvPath) . '"', $body);
    }

    public function testBodyContainsCsvContent(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $api->createAffiliateLostOrders($this->createMockAffiliate(), $this->validCsvPath);

        $request = $this->requestHistory[0]['request'];
        $body = (string) $request->getBody();
        $this->assertStringContainsString('campaign_unique', $body);
        $this->assertStringContainsString('ORD-001', $body);
    }

    // --- Success Response ---

    public function testReturnsPsr7ResponseOnSuccess(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $response = $api->createAffiliateLostOrders($this->createMockAffiliate(), $this->validCsvPath);

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $response);
    }

    public function testReturns201StatusCodeOnSuccess(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $response = $api->createAffiliateLostOrders($this->createMockAffiliate(), $this->validCsvPath);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testResponseBodyContainsFileInfo(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(201, [], $this->successResponseBody()),
        ]);

        $response = $api->createAffiliateLostOrders($this->createMockAffiliate(), $this->validCsvPath);

        $data = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($data);
        $this->assertSame('12345', $data[0]['id']);
        $this->assertSame('finished', $data[0]['uploadStatus']);
    }

    // --- Error Responses ---

    public function testThrowsApiExceptionOn422WithStringErrors(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(422, [], json_encode([
                'errors' => ['Failed to initiate lost orders processing.'],
            ])),
        ]);

        $this->expectException(\TPerformant\API\Exception\APIException::class);
        $this->expectExceptionMessageMatches('/Failed to initiate lost orders processing/');

        $api->createAffiliateLostOrders($this->createMockAffiliate(), $this->validCsvPath);
    }

    public function testThrowsApiExceptionOn400WithStructuredErrors(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(400, [], json_encode([
                'errors' => [
                    ['title' => 'Invalid file format'],
                ],
            ])),
        ]);

        $this->expectException(\TPerformant\API\Exception\APIException::class);
        $this->expectExceptionMessageMatches('/Invalid file format/');

        $api->createAffiliateLostOrders($this->createMockAffiliate(), $this->validCsvPath);
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

        $api->createAffiliateLostOrders($this->createMockAffiliate(), $this->validCsvPath);
    }

    public function testApiExceptionContainsHttpStatusCode(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(422, [], json_encode([
                'errors' => ['Some error'],
            ])),
        ]);

        try {
            $api->createAffiliateLostOrders($this->createMockAffiliate(), $this->validCsvPath);
            $this->fail('Expected APIException was not thrown');
        } catch (\TPerformant\API\Exception\APIException $e) {
            $this->assertSame(422, $e->getCode());
        }
    }

    public function testFallsBackToReasonPhraseWhenNoErrorsKeyInBody(): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(400, [], json_encode(['message' => 'something went wrong'])),
        ]);

        $this->expectException(\TPerformant\API\Exception\APIException::class);
        $this->expectExceptionMessageMatches('/Bad Request/');

        $api->createAffiliateLostOrders($this->createMockAffiliate(), $this->validCsvPath);
    }
}
