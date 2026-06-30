<?php

namespace TPerformant\API\Tests\Filter;

use PHPUnit\Framework\TestCase;
use TPerformant\API\Filter\AdvertiserCommissionFilter;

class AdvertiserCommissionFilterTest extends TestCase
{
    public function testTransactionIdMapsToTransactionIdField(): void
    {
        $filter = new AdvertiserCommissionFilter();
        $filter->transactionId('abc123');

        $this->assertSame(['filter' => ['transaction_id' => 'abc123']], $filter->toParams());
    }

    public function testInheritsQueryFromCommissionFilter(): void
    {
        $filter = new AdvertiserCommissionFilter();
        $filter->query('search');

        $this->assertSame(['filter' => ['query' => 'search']], $filter->toParams());
    }

    public function testInheritsStatusFromCommissionFilter(): void
    {
        $filter = new AdvertiserCommissionFilter();
        $filter->status('rejected');

        $this->assertSame(['filter' => ['status' => 'rejected']], $filter->toParams());
    }

    public function testStartDateMapsToStartDateField(): void
    {
        $filter = new AdvertiserCommissionFilter();
        $filter->startDate('2024-01-01');

        $this->assertSame(['filter' => ['start_date' => '2024-01-01']], $filter->toParams());
    }

    public function testEndDateMapsToEndDateField(): void
    {
        $filter = new AdvertiserCommissionFilter();
        $filter->endDate('2024-12-31');

        $this->assertSame(['filter' => ['end_date' => '2024-12-31']], $filter->toParams());
    }

    public function testTypeMapsToTypeField(): void
    {
        $filter = new AdvertiserCommissionFilter();
        $filter->type('lead');

        $this->assertSame(['filter' => ['type' => 'lead']], $filter->toParams());
    }

    public function testAllFieldsAreMergedCorrectly(): void
    {
        $filter = new AdvertiserCommissionFilter();
        $filter->transactionId('tx99')->status('accepted')->startDate('2024-03-01');

        $params = $filter->toParams();

        $this->assertSame([
            'transaction_id' => 'tx99',
            'status'         => 'accepted',
            'start_date'     => '2024-03-01',
        ], $params['filter']);
    }
}
