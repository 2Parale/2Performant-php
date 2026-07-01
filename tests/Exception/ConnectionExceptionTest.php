<?php

namespace TPerformant\API\Tests\Exception;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Exception\ConnectionException;

class ConnectionExceptionTest extends TestCase
{
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new Request('GET', 'https://api.2performant.com/endpoint');
    }

    public function testCreateReturnsConnectionExceptionInstance(): void
    {
        $guzzleException = new ConnectException('Connection refused', $this->request);

        $exception = ConnectionException::create($guzzleException);

        $this->assertInstanceOf(ConnectionException::class, $exception);
    }

    public function testCreateMessageContainsOriginalMessage(): void
    {
        $guzzleException = new ConnectException('Connection refused', $this->request);

        $exception = ConnectionException::create($guzzleException);

        $this->assertStringContainsString('Connection refused', $exception->getMessage());
    }

    public function testCreateMessageContainsRequestUri(): void
    {
        $guzzleException = new ConnectException('Connection refused', $this->request);

        $exception = ConnectionException::create($guzzleException);

        $this->assertStringContainsString('https://api.2performant.com/endpoint', $exception->getMessage());
    }

    public function testCreateCodeIsAlwaysZero(): void
    {
        $guzzleException = new ConnectException('Connection refused', $this->request);

        $exception = ConnectionException::create($guzzleException);

        $this->assertSame(0, $exception->getCode());
    }

    public function testCreatePreservesPreviousException(): void
    {
        $guzzleException = new ConnectException('Connection refused', $this->request);

        $exception = ConnectionException::create($guzzleException);

        $this->assertSame($guzzleException, $exception->getPrevious());
    }

    public function testCreateMessageMatchesExpectedFormat(): void
    {
        $guzzleException = new ConnectException('Connection refused', $this->request);

        $exception = ConnectionException::create($guzzleException);

        $expected = 'Connection error Connection refused on https://api.2performant.com/endpoint';
        $this->assertSame($expected, $exception->getMessage());
    }
}
