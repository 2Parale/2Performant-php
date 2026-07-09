<?php

namespace TPerformant\API\Tests\Model;

use PHPUnit\Framework\TestCase;
use TPerformant\API\HTTP\Affiliate as HttpAffiliate;
use TPerformant\API\Model\AffiliateProgram;

class AffiliateProgramQuicklinkTest extends TestCase
{
    // --- Delegation ---

    public function testDelegatesToRequesterGetQuicklink(): void
    {
        $affiliate = $this->createMock(HttpAffiliate::class);
        $affiliate
            ->expects($this->once())
            ->method('getQuicklink')
            ->willReturn('https://event.2performant.com/events/click?ad_type=quicklink');

        $program = new AffiliateProgram((object) ['id' => 1, 'name' => 'Test Program'], $affiliate);
        $program->getQuicklink('https://example.com');
    }

    public function testPassesUrlToRequester(): void
    {
        $affiliate = $this->createMock(HttpAffiliate::class);
        $affiliate
            ->expects($this->once())
            ->method('getQuicklink')
            ->with('https://example.com/product', $this->anything())
            ->willReturn('https://event.2performant.com/events/click?ad_type=quicklink');

        $program = new AffiliateProgram((object) ['id' => 1], $affiliate);
        $program->getQuicklink('https://example.com/product');
    }

    public function testPassesSelfAsProgram(): void
    {
        $affiliate = $this->createMock(HttpAffiliate::class);

        // Create the program first so we can capture the exact instance reference
        $program = new AffiliateProgram((object) ['id' => 1, 'unique_code' => 'prog-abc'], $affiliate);

        $affiliate
            ->expects($this->once())
            ->method('getQuicklink')
            ->with($this->anything(), $this->identicalTo($program))
            ->willReturn('https://event.2performant.com/events/click?ad_type=quicklink');

        $program->getQuicklink('https://example.com');
    }

    public function testReturnsResultFromRequester(): void
    {
        $expected = 'https://event.2performant.com/events/click?ad_type=quicklink&aff_code=aff-1&unique=prog-abc&redirect_to=https%3A%2F%2Fexample.com';

        $affiliate = $this->createMock(HttpAffiliate::class);
        $affiliate->method('getQuicklink')->willReturn($expected);

        $program = new AffiliateProgram((object) ['id' => 1], $affiliate);

        $this->assertSame($expected, $program->getQuicklink('https://example.com'));
    }
}
