<?php

namespace TPerformant\API\Tests\Filter;

use PHPUnit\Framework\TestCase;
use TPerformant\API\Filter\AffiliateCommissionFilter;

class AffiliateCommissionFilterTest extends TestCase
{
    public function testInheritsQueryFromCommissionFilter(): void
    {
        $filter = new AffiliateCommissionFilter();
        $filter->query('term');

        $this->assertSame(['filter' => ['query' => 'term']], $filter->toParams());
    }

    public function testInheritsStatusFromCommissionFilter(): void
    {
        $filter = new AffiliateCommissionFilter();
        $filter->status('accepted');

        $this->assertSame(['filter' => ['status' => 'accepted']], $filter->toParams());
    }

    public function testInheritsDateFromCommissionFilter(): void
    {
        $filter = new AffiliateCommissionFilter();
        $filter->date('2024-06-01');

        $this->assertSame(['filter' => ['date' => '2024-06-01']], $filter->toParams());
    }

    public function testStartDateMapsToStartDateField(): void
    {
        $filter = new AffiliateCommissionFilter();
        $filter->startDate('2024-01-01');

        $this->assertSame(['filter' => ['start_date' => '2024-01-01']], $filter->toParams());
    }

    public function testEndDateMapsToEndDateField(): void
    {
        $filter = new AffiliateCommissionFilter();
        $filter->endDate('2024-12-31');

        $this->assertSame(['filter' => ['end_date' => '2024-12-31']], $filter->toParams());
    }

    public function testTypeMapsToTypeField(): void
    {
        $filter = new AffiliateCommissionFilter();
        $filter->type('sale');

        $this->assertSame(['filter' => ['type' => 'sale']], $filter->toParams());
    }

    public function testInheritedAndOwnFiltersAreMerged(): void
    {
        $filter = new AffiliateCommissionFilter();
        $filter->status('pending')->startDate('2024-01-01')->endDate('2024-12-31')->type('sale');

        $params = $filter->toParams();

        $this->assertSame([
            'status'     => 'pending',
            'start_date' => '2024-01-01',
            'end_date'   => '2024-12-31',
            'type'       => 'sale',
        ], $params['filter']);
    }
}
