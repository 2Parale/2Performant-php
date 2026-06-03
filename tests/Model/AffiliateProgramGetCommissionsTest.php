<?php

namespace TPerformant\API\Tests\Model;

use PHPUnit\Framework\TestCase;
use TPerformant\API\HTTP\Affiliate as HttpAffiliate;
use TPerformant\API\Model\AffiliateProgram;

class AffiliateProgramGetCommissionsTest extends TestCase
{
    public function testGetCommissionsDelegatesToRequesterWithProgramFilter(): void
    {
        $affiliate = $this->createMock(HttpAffiliate::class);
        $affiliate
            ->expects($this->once())
            ->method('getCommissions')
            ->with($this->callback(function ($filter) {
                $params = $filter->toParams(); // or however CollectionFilter exposes params
                $this->assertStringContainsString('program_name:My Program', $params['filter']['query']);
                $this->assertStringContainsString('campaign_name:My Program', $params['filter']['query']);
                return true;
            }))
            ->willReturn(['mocked_commissions']);

        $program = new AffiliateProgram(
            (object) ['id' => 1, 'name' => 'My Program'],
            $affiliate
        );

        $result = $program->getCommissions();
        $this->assertSame(['mocked_commissions'], $result);
    }

    public function testGetCommissionsUsesRequesterNotUndefinedOwner(): void
    {
        // Regression: master used $this->owner which does not exist on GenericEntity
        $affiliate = $this->createMock(HttpAffiliate::class);
        $affiliate->method('getCommissions')->willReturn([]);

        $program = new AffiliateProgram((object) ['name' => 'Test'], $affiliate);

        // Would throw/fatal on master; should succeed after fix
        $this->assertSame([], $program->getCommissions());
    }
}
