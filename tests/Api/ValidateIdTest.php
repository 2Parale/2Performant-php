<?php

namespace TPerformant\API\Tests\Api;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\Exception\TPException;
use TPerformant\API\HTTP\Affiliate;

/**
 * Dedicated tests for the private validateId() method, exercised through
 * getAffiliateProgram() which is the simplest single-ID public method.
 */
class ValidateIdTest extends TestCase
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

    public function invalidIdProvider(): array
    {
        return [
            'boolean true'        => [true],
            'boolean false'       => [false],
            'null'                => [null],
            'empty string'        => [''],
            'whitespace string'   => ['   '],
            'zero'                => [0],
            'negative integer'    => [-1],
            'float'               => [1.5],
            'string with slash'   => ['/foo'],
            'string with space'   => ['foo bar'],
            'string with special' => ['foo!'],
            'string with at sign' => ['foo@bar'],
        ];
    }

    /**
     * @dataProvider invalidIdProvider
     */
    public function testInvalidIdThrowsTpException($invalidId): void
    {
        $api = $this->createApiWithMockHttp([]);

        $this->expectException(TPException::class);

        $api->getAffiliateProgram($this->createMockAffiliate(), $invalidId);
    }

    /**
     * @dataProvider invalidIdProvider
     */
    public function testInvalidIdExceptionMessageContainsCallerName($invalidId): void
    {
        $api = $this->createApiWithMockHttp([]);

        try {
            $api->getAffiliateProgram($this->createMockAffiliate(), $invalidId);
            $this->fail('Expected TPException was not thrown');
        } catch (TPException $e) {
            $this->assertStringContainsString('getAffiliateProgram', $e->getMessage());
        }
    }

    /**
     * @dataProvider invalidIdProvider
     */
    public function testInvalidIdDoesNotSendHttpRequest($invalidId): void
    {
        // No responses queued: if a request were made, MockHandler would throw
        // an OutOfBoundsException, causing a different test failure.
        $api = $this->createApiWithMockHttp([]);

        try {
            $api->getAffiliateProgram($this->createMockAffiliate(), $invalidId);
        } catch (TPException $e) {
            // expected
        }

        $this->assertCount(0, $this->requestHistory);
    }

    public function validIdProvider(): array
    {
        return [
            'positive integer'        => [1],
            'large integer'           => [99999],
            'integer as string'       => ['42'],
            'alphanumeric slug'       => ['my-program'],
            'underscore slug'         => ['my_program'],
            'uppercase slug'          => ['PROGRAM123'],
            'mixed alphanumeric slug' => ['Program-1_A'],
        ];
    }

    /**
     * @dataProvider validIdProvider
     */
    public function testValidIdDoesNotThrow($validId): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['program' => ['id' => 1, 'name' => 'Test']])),
        ]);

        // Should not throw — validate no exception is raised
        $response = $api->getAffiliateProgram($this->createMockAffiliate(), $validId);

        $this->assertInstanceOf(\TPerformant\API\HTTP\ApiResponse::class, $response);
    }

    /**
     * @dataProvider validIdProvider
     */
    public function testValidIdSendsHttpRequest($validId): void
    {
        $api = $this->createApiWithMockHttp([
            new Response(200, [], json_encode(['program' => ['id' => 1, 'name' => 'Test']])),
        ]);

        $api->getAffiliateProgram($this->createMockAffiliate(), $validId);

        $this->assertCount(1, $this->requestHistory);
    }
}
