<?php

namespace TPerformant\API\Tests\HTTP;

use PHPUnit\Framework\TestCase;
use TPerformant\API\HTTP\ApiResponse;
use TPerformant\API\Exception\APIException;
use TPerformant\API\Exception\ClientException;
use TPerformant\API\Exception\InvalidResponseException;
use TPerformant\API\Model\Affiliate as ModelAffiliate;
use TPerformant\API\Model\AffiliateProgram;
use TPerformant\API\Model\Program;
use GuzzleHttp\Psr7\Response;

class ApiResponseTest extends TestCase {
    public function testValidateResponseParsesErrorsFromArray(): void
    {
        $errors = [
            ['title' => 'Validation failed', 'detail' => 'Amount invalid'],
        ];
        $response = new Response(422, [], json_encode(['errors' => $errors]));
        $this->expectException(APIException::class);
        $this->expectExceptionMessage('Validation failed');
        ApiResponse::validateResponse($response);
    }

    // --- validateResponse() returns decoded data, not the response object ---

    public function testValidateResponseReturnsDecodedJsonData(): void
    {
        $payload = ['result' => 'ok', 'count' => 3];
        $response = new Response(200, [], json_encode($payload));

        $data = ApiResponse::validateResponse($response);

        $this->assertIsObject($data);
        $this->assertObjectHasProperty('result', $data);
        $this->assertSame('ok', $data->result);
        $this->assertSame(3, $data->count);
    }

    public function testValidateResponseReturnsNullForNoContent(): void
    {
        $response = new Response(204, [], '');

        $data = ApiResponse::validateResponse($response);

        $this->assertNull($data);
    }

    // --- getHeader() returns a string, not an array ---

    public function testGetHeaderReturnsStringValue(): void
    {
        $response = new Response(200, ['access-token' => 'my-token'], json_encode(['result' => 'ok']));
        $apiResponse = new ApiResponse($response, 'result');

        $value = $apiResponse->getHeader('access-token');

        $this->assertIsString($value);
        $this->assertSame('my-token', $value);
    }

    public function testGetHeaderReturnsFalseWhenAbsent(): void
    {
        $response = new Response(200, [], json_encode(['result' => 'ok']));
        $apiResponse = new ApiResponse($response, 'result');

        $this->assertFalse($apiResponse->getHeader('non-existent-header'));
    }

    public function testGetHeaderReturnsFirstValueWhenMultiplePresent(): void
    {
        $response = new Response(200, ['x-custom' => ['first', 'second']], json_encode(['result' => 'ok']));
        $apiResponse = new ApiResponse($response, 'result');

        $value = $apiResponse->getHeader('x-custom');

        $this->assertSame('first', $value);
    }

    // --- getMeta() returns parsed metadata ---

    public function testGetMetaReturnsParsedMetadata(): void
    {
        $payload = [
            'result'   => ['id' => 1],
            'metadata' => ['total' => 42, 'page' => 1, 'per_page' => 10],
        ];
        $response    = new Response(200, [], json_encode($payload));
        $apiResponse = new ApiResponse($response, 'result');

        $meta = $apiResponse->getMeta();

        $this->assertIsObject($meta);
        $this->assertSame(42, $meta->total);
        $this->assertSame(1, $meta->page);
        $this->assertSame(10, $meta->per_page);
    }

    // --- Deprecation notices ---

    public function testDeprecationNoticesAreTriggeredForEachDeprecation(): void
    {
        $payload = [
            'result'   => ['id' => 1],
            'metadata' => [
                'deprecations' => [
                    ['title' => 'Old endpoint',  'detail' => 'Use /v2/ instead'],
                    ['title' => 'Legacy field'],
                ],
            ],
        ];
        $response = new Response(200, [], json_encode($payload));

        $deprecations = [];
        set_error_handler(
            function (int $errno, string $errstr) use (&$deprecations): bool {
                $deprecations[] = $errstr;
                return true;
            },
            E_USER_DEPRECATED
        );

        try {
            new ApiResponse($response, 'result');
        } finally {
            restore_error_handler();
        }

        $this->assertCount(2, $deprecations);
        $this->assertStringContainsString('Old endpoint', $deprecations[0]);
        $this->assertStringContainsString('Use /v2/ instead', $deprecations[0]);
        $this->assertStringContainsString('Legacy field', $deprecations[1]);
    }

    // --- _convert() ---

    public function testConvertWithUserExpectedAndRoleCreatesRoleSubclass(): void
    {
        $payload = [
            'user' => ['role' => 'affiliate', 'id' => 5, 'email' => 'aff@example.com'],
        ];
        $response    = new Response(200, [], json_encode($payload));
        $apiResponse = new ApiResponse($response, 'user');

        $this->assertInstanceOf(ModelAffiliate::class, $apiResponse->getBody());
    }

    public function testConvertPrefersRolePrefixedModelClassForOwnerWithRole(): void
    {
        $owner = $this->createMock(\TPerformant\API\HTTP\Affiliate::class);
        $owner->method('getRole')->willReturn('affiliate');

        $payload = [
            'program' => ['id' => 10, 'name' => 'Test Program', 'slug' => 'test-program'],
        ];
        $response    = new Response(200, [], json_encode($payload));
        $apiResponse = new ApiResponse($response, 'program', $owner);

        $this->assertInstanceOf(AffiliateProgram::class, $apiResponse->getBody());
    }

    public function testConvertWithPluralExpectedKeyMapsToArrayOfModelObjects(): void
    {
        $payload = [
            'programs' => [
                ['id' => 1, 'name' => 'Program One'],
                ['id' => 2, 'name' => 'Program Two'],
            ],
        ];
        $response    = new Response(200, [], json_encode($payload));
        $apiResponse = new ApiResponse($response, 'programs');

        $body = $apiResponse->getBody();

        $this->assertIsArray($body);
        $this->assertCount(2, $body);
        $this->assertInstanceOf(Program::class, $body[0]);
        $this->assertInstanceOf(Program::class, $body[1]);
    }

    public function testConvertReturnsRawStdClassWhenNoMatchingClassExists(): void
    {
        $payload = ['foobar' => ['id' => 99, 'custom' => 'value']];
        $response    = new Response(200, [], json_encode($payload));
        $apiResponse = new ApiResponse($response, 'foobar');

        $body = $apiResponse->getBody();

        $this->assertInstanceOf(\stdClass::class, $body);
        $this->assertSame(99, $body->id);
        $this->assertSame('value', $body->custom);
    }

    // --- Exception cases ---

    public function testThrowsInvalidResponseExceptionWhenExpectedKeyIsMissing(): void
    {
        $payload  = ['other_key' => ['id' => 1]];
        $response = new Response(200, [], json_encode($payload));

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessageMatches('/expected property/i');

        new ApiResponse($response, 'missing_key');
    }

    public function testClientExceptionThrownForClientErrorWithUnparsableBody(): void
    {
        $response = new Response(404, [], 'Not Found');

        $this->expectException(ClientException::class);

        ApiResponse::validateResponse($response);
    }

    public function testNonJsonResponseBodyThrowsInvalidResponseExceptionWithBodySummary(): void
    {
        $response = new Response(200, [], '<html>Server Error Page</html>');

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessageMatches('/Response body must be valid JSON/i');

        ApiResponse::validateResponse($response);
    }
}
