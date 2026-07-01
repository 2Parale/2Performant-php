<?php

namespace TPerformant\API\Tests\Model;

use PHPUnit\Framework\TestCase;
use TPerformant\API\HTTP\Affiliate as HttpAffiliate;
use TPerformant\API\Model\AffiliateProgram;
use TPerformant\API\Model\Commission;
use TPerformant\API\Model\Program;

class CommissionTest extends TestCase
{
    public function testProgramIsNullByDefault(): void
    {
        $commission = new Commission((object)['id' => 1]);
        $this->assertNull($commission->getProgram());
    }

    // -------------------------------------------------------------------------
    // Nested Program hydration via classMap (no requester → base Program class)
    // -------------------------------------------------------------------------

    public function testClassMapHydratesProgramAsModelInstance(): void
    {
        $commission = new Commission((object)[
            'id'      => 1,
            'program' => (object)['id' => 5, 'name' => 'Demo Program'],
        ]);

        $this->assertInstanceOf(Program::class, $commission->getProgram());
    }

    public function testClassMapPassesProgramFieldsCorrectly(): void
    {
        $commission = new Commission((object)[
            'id'      => 1,
            'program' => (object)['id' => 9, 'name' => 'Cool Program'],
        ]);

        $program = $commission->getProgram();
        $this->assertSame(9, $program->getId());
        $this->assertSame('Cool Program', $program->getName());
    }

    // -------------------------------------------------------------------------
    // Role-aware nested Program hydration (affiliate requester → AffiliateProgram)
    // -------------------------------------------------------------------------

    public function testClassMapHydratesProgramAsAffiliateProgramWhenRequesterIsAffiliate(): void
    {
        $affiliate = $this->createMock(HttpAffiliate::class);
        $affiliate->method('getRole')->willReturn('affiliate');

        $commission = new Commission(
            (object)['id' => 1, 'program' => (object)['id' => 3]],
            $affiliate
        );

        $this->assertInstanceOf(AffiliateProgram::class, $commission->getProgram());
    }
}
