<?php

namespace TPerformant\API\Tests\Exception;

use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Exception\ClientException;

class ClientExceptionTest extends TestCase
{
    private Request $request;
    private Response $response;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request  = new Request('GET', 'https://api.2performant.com/endpoint');
        $this->response = new Response(404);
    }

    public function testCreateReturnsClientExceptionInstance(): void
    {
        $guzzleException = new GuzzleClientException('Guzzle error', $this->request, $this->response);

        $exception = ClientException::create($guzzleException);

        $this->assertInstanceOf(ClientException::class, $exception);
    }

    public function testCreateMessageContainsStatusCode(): void
    {
        $guzzleException = new GuzzleClientException('Guzzle error', $this->request, $this->response);

        $exception = ClientException::create($guzzleException);

        $this->assertStringContainsString('404', $exception->getMessage());
    }

    public function testCreateMessageContainsReasonPhrase(): void
    {
        $guzzleException = new GuzzleClientException('Guzzle error', $this->request, $this->response);

        $exception = ClientException::create($guzzleException);

        $this->assertStringContainsString('Not Found', $exception->getMessage());
    }

    public function testCreateMessageContainsOriginalGuzzleMessage(): void
    {
        $guzzleException = new GuzzleClientException('Access token expired', $this->request, $this->response);

        $exception = ClientException::create($guzzleException);

        $this->assertStringContainsString('Access token expired', $exception->getMessage());
    }

    public function testCreateMessageContainsRequestUri(): void
    {
        $guzzleException = new GuzzleClientException('Guzzle error', $this->request, $this->response);

        $exception = ClientException::create($guzzleException);

        $this->assertStringContainsString('https://api.2performant.com/endpoint', $exception->getMessage());
    }

    public function testCreatePropagatesStatusCode(): void
    {
        $guzzleException = new GuzzleClientException('Guzzle error', $this->request, new Response(403));

        $exception = ClientException::create($guzzleException);

        $this->assertSame(403, $exception->getCode());
    }

    public function testCreatePreservesPreviousException(): void
    {
        $guzzleException = new GuzzleClientException('Guzzle error', $this->request, $this->response);

        $exception = ClientException::create($guzzleException);

        $this->assertSame($guzzleException, $exception->getPrevious());
    }

    public function testCreateMessageMatchesExpectedFormat(): void
    {
        $guzzleException = new GuzzleClientException('Guzzle error', $this->request, $this->response);

        $exception = ClientException::create($guzzleException);

        $expected = 'Request could not be processed (404 Not Found): Guzzle error on https://api.2performant.com/endpoint';
        $this->assertSame($expected, $exception->getMessage());
    }
}
