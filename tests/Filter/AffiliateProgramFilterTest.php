<?php

namespace TPerformant\API\Tests\Filter;

use PHPUnit\Framework\TestCase;
use TPerformant\API\Filter\AffiliateProgramFilter;

class AffiliateProgramFilterTest extends TestCase
{
    public function testRelationMapsToRelationField(): void
    {
        $filter = new AffiliateProgramFilter();
        $filter->relation('accepted');

        $this->assertSame(['filter' => ['relation' => 'accepted']], $filter->toParams());
    }

    public function testInheritsQueryFromProgramFilter(): void
    {
        $filter = new AffiliateProgramFilter();
        $filter->query('electronics');

        $this->assertSame(['filter' => ['query' => 'electronics']], $filter->toParams());
    }

    public function testInheritsCategoryFromProgramFilter(): void
    {
        $filter = new AffiliateProgramFilter();
        $filter->category('fashion');

        $this->assertSame(['filter' => ['category' => 'fashion']], $filter->toParams());
    }

    public function testInheritsCountryFromProgramFilter(): void
    {
        $filter = new AffiliateProgramFilter();
        $filter->country('RO');

        $this->assertSame(['filter' => ['country' => 'RO']], $filter->toParams());
    }

    public function testInheritedAndOwnFieldsAreMerged(): void
    {
        $filter = new AffiliateProgramFilter();
        $filter->query('shop')->category('retail')->relation('pending');

        $params = $filter->toParams();

        $this->assertSame([
            'query'    => 'shop',
            'category' => 'retail',
            'relation' => 'pending',
        ], $params['filter']);
    }
}
