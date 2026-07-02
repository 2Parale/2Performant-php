<?php

namespace TPerformant\API\Tests\Model;

use PHPUnit\Framework\TestCase;
use TPerformant\API\HTTP\Affiliate as HttpAffiliate;
use TPerformant\API\Model\AffiliateProgram;
use TPerformant\API\Model\Banner;
use TPerformant\API\Model\Program;

class BannerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // classMap: program → Program instance (no requester)
    // -------------------------------------------------------------------------

    public function testClassMapHydratesProgramAsModelInstance(): void
    {
        $banner = new Banner((object)[
            'id'      => 1,
            'program' => (object)['id' => 5, 'name' => 'Shop Program'],
        ]);

        $this->assertInstanceOf(Program::class, $banner->getProgram());
    }

    public function testClassMapPassesProgramFieldsCorrectly(): void
    {
        $banner = new Banner((object)[
            'id'      => 1,
            'program' => (object)['id' => 12, 'name' => 'Fashion Store'],
        ]);

        $program = $banner->getProgram();
        $this->assertSame(12, $program->getId());
        $this->assertSame('Fashion Store', $program->getName());
    }

    public function testProgramIsNullWhenNotInPayload(): void
    {
        $banner = new Banner((object)['id' => 1]);
        $this->assertNull($banner->getProgram());
    }

    // -------------------------------------------------------------------------
    // Role-aware classMap: affiliate requester → AffiliateProgram
    // -------------------------------------------------------------------------

    public function testClassMapResolvesAffiliateProgramForAffiliateRequester(): void
    {
        $affiliate = $this->createMock(HttpAffiliate::class);
        $affiliate->method('getRole')->willReturn('affiliate');

        $banner = new Banner(
            (object)['id' => 1, 'program' => (object)['id' => 3]],
            $affiliate
        );

        $this->assertInstanceOf(AffiliateProgram::class, $banner->getProgram());
    }
}
