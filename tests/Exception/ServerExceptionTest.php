<?php

namespace TPerformant\API\Tests\Exception;

use GuzzleHttp\Exception\ServerException as GuzzleServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Exception\ServerException;

class ServerExceptionTest extends TestCase
{
    private Request $request;
    private Response $response;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request  = new Request('GET', 'https://api.2performant.com/endpoint');
        $this->response = new Response(500);
    }

    public function testCreateReturnsServerExceptionInstance(): void
    {
        $guzzleException = new GuzzleServerException('Guzzle error', $this->request, $this->response);

        $exception = ServerException::create($guzzleException);

        $this->assertInstanceOf(ServerException::class, $exception);
    }

    public function testCreateMessageContainsStatusCode(): void
    {
        $guzzleException = new GuzzleServerException('Guzzle error', $this->request, new Response(503));

        $exception = ServerException::create($guzzleException);

        $this->assertStringContainsString('503', $exception->getMessage());
    }

    public function testCreateMessageContainsReasonPhrase(): void
    {
        $guzzleException = new GuzzleServerException('Guzzle error', $this->request, $this->response);

        $exception = ServerException::create($guzzleException);

        $this->assertStringContainsString('Internal Server Error', $exception->getMessage());
    }

    public function testCreateMessageContainsOriginalGuzzleMessage(): void
    {
        $guzzleException = new GuzzleServerException('Upstream timeout occurred', $this->request, $this->response);

        $exception = ServerException::create($guzzleException);

        $this->assertStringContainsString('Upstream timeout occurred', $exception->getMessage());
    }

    public function testCreateMessageContainsRequestUri(): void
    {
        $guzzleException = new GuzzleServerException('Guzzle error', $this->request, $this->response);

        $exception = ServerException::create($guzzleException);

        $this->assertStringContainsString('https://api.2performant.com/endpoint', $exception->getMessage());
    }

    public function testCreatePropagatesStatusCode(): void
    {
        $guzzleException = new GuzzleServerException('Guzzle error', $this->request, new Response(503));

        $exception = ServerException::create($guzzleException);

        $this->assertSame(503, $exception->getCode());
    }

    public function testCreatePreservesPreviousException(): void
    {
        $guzzleException = new GuzzleServerException('Guzzle error', $this->request, $this->response);

        $exception = ServerException::create($guzzleException);

        $this->assertSame($guzzleException, $exception->getPrevious());
    }

    public function testCreateMessageMatchesExpectedFormat(): void
    {
        $guzzleException = new GuzzleServerException('Guzzle error', $this->request, $this->response);

        $exception = ServerException::create($guzzleException);

        $expected = 'API server error (500 Internal Server Error): Guzzle error on https://api.2performant.com/endpoint';
        $this->assertSame($expected, $exception->getMessage());
    }
}
