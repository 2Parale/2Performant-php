<?php

namespace TPerformant\API\Tests\Model;

use PHPUnit\Framework\TestCase;
use TPerformant\API\HTTP\Affiliate as HttpAffiliate;
use TPerformant\API\Model\AdvertiserPromotion;
use TPerformant\API\Model\AffiliateProgram;
use TPerformant\API\Model\Program;

class AdvertiserPromotionTest extends TestCase
{

    public function testProgramIsNullByDefault(): void
    {
        $promotion = new AdvertiserPromotion((object)['id' => 1]);
        $this->assertNull($promotion->getProgram());
    }

    // -------------------------------------------------------------------------
    // classMap: program → Program instance (no requester)
    // -------------------------------------------------------------------------

    public function testClassMapHydratesProgramAsModelInstance(): void
    {
        $promotion = new AdvertiserPromotion((object)[
            'id'      => 1,
            'program' => (object)['id' => 5, 'name' => 'My Program'],
        ]);

        $this->assertInstanceOf(Program::class, $promotion->getProgram());
    }

    public function testClassMapPassesProgramFieldsCorrectly(): void
    {
        $promotion = new AdvertiserPromotion((object)[
            'id'      => 1,
            'program' => (object)['id' => 11, 'name' => 'Top Store'],
        ]);

        $this->assertSame(11, $promotion->getProgram()->getId());
        $this->assertSame('Top Store', $promotion->getProgram()->getName());
    }

    // -------------------------------------------------------------------------
    // Role-aware classMap: affiliate requester → AffiliateProgram
    // -------------------------------------------------------------------------

    public function testClassMapResolvesAffiliateProgramForAffiliateRequester(): void
    {
        $affiliate = $this->createMock(HttpAffiliate::class);
        $affiliate->method('getRole')->willReturn('affiliate');

        $promotion = new AdvertiserPromotion(
            (object)['id' => 1, 'program' => (object)['id' => 3]],
            $affiliate
        );

        $this->assertInstanceOf(AffiliateProgram::class, $promotion->getProgram());
    }
}
