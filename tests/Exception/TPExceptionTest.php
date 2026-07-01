<?php

namespace TPerformant\API\Tests\Exception;

use PHPUnit\Framework\TestCase;
use TPerformant\API\Exception\TPException;

class TPExceptionTest extends TestCase
{
    public function testGetMessageReturnsConstructorMessage(): void
    {
        $exception = new TPException('Something went wrong');

        $this->assertSame('Something went wrong', $exception->getMessage());
    }

    public function testGetCodeReturnsConstructorCode(): void
    {
        $exception = new TPException('Error', 42);

        $this->assertSame(42, $exception->getCode());
    }

    public function testGetDataReturnsNullByDefault(): void
    {
        $exception = new TPException('Error');

        $this->assertNull($exception->getData());
    }

    public function testGetDataReturnsCustomArrayData(): void
    {
        $data = ['key' => 'value', 'count' => 5];
        $exception = new TPException('Error', 0, null, $data);

        $this->assertSame($data, $exception->getData());
    }

    public function testGetDataReturnsCustomObjectData(): void
    {
        $data = new \stdClass();
        $data->foo = 'bar';
        $exception = new TPException('Error', 0, null, $data);

        $this->assertSame($data, $exception->getData());
    }

    public function testDefaultCodeIsZero(): void
    {
        $exception = new TPException('Error');

        $this->assertSame(0, $exception->getCode());
    }

    public function testGetPreviousReturnsConstructorPrevious(): void
    {
        $previous = new \RuntimeException('root cause');
        $exception = new TPException('Error', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
