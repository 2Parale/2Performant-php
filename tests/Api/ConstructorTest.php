<?php

namespace TPerformant\API\Tests\Api;

use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;

class ConstructorTest extends TestCase
{
    public function testAcceptsHttpsUrl(): void
    {
        // Should not throw
        $api = new Api('https://api.2performant.com');
        $this->assertInstanceOf(Api::class, $api);
    }

    public function testAcceptsHttpsUrlWithUppercaseScheme(): void
    {
        $api = new Api('HTTPS://api.2performant.com');
        $this->assertInstanceOf(Api::class, $api);
    }

    public function testRejectsHttpUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Api('http://api.2performant.com');
    }

    public function testRejectsUrlWithNoScheme(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Api('api.2performant.com');
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Api('');
    }
}
