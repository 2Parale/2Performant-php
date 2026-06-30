<?php

namespace TPerformant\API\Tests\Filter;

use PHPUnit\Framework\TestCase;
use TPerformant\API\Filter\CommissionFilter;

class CommissionFilterTest extends TestCase
{
    public function testQueryMapsToQueryField(): void
    {
        $filter = new CommissionFilter();
        $filter->query('search term');

        $this->assertSame(['filter' => ['query' => 'search term']], $filter->toParams());
    }

    public function testStatusMapsToStatusField(): void
    {
        $filter = new CommissionFilter();
        $filter->status('accepted');

        $this->assertSame(['filter' => ['status' => 'accepted']], $filter->toParams());
    }

    public function testDateMapsToDateField(): void
    {
        $filter = new CommissionFilter();
        $filter->date('2024-01-01');

        $this->assertSame(['filter' => ['date' => '2024-01-01']], $filter->toParams());
    }

    public function testMultipleFiltersAreMergedUnderFilterKey(): void
    {
        $filter = new CommissionFilter();
        $filter->query('q')->status('pending');

        $params = $filter->toParams();

        $this->assertSame(['query' => 'q', 'status' => 'pending'], $params['filter']);
    }

    public function testToParamsReturnsEmptyArrayWhenNoFiltersApplied(): void
    {
        $filter = new CommissionFilter();

        $this->assertSame([], $filter->toParams());
    }
}
