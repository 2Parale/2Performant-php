<?php

namespace TPerformant\API\Tests\Filter;

use PHPUnit\Framework\TestCase;
use TPerformant\API\Filter\AffiliateCommissionSort;

class AffiliateCommissionSortTest extends TestCase
{
    public function testTransactionIdAscSortsCorrectly(): void
    {
        $sort = new AffiliateCommissionSort();
        $sort->transactionIdAsc();

        $this->assertSame(['sort' => ['transaction_id' => 'asc']], $sort->toParams());
    }

    public function testTransactionIdDescSortsCorrectly(): void
    {
        $sort = new AffiliateCommissionSort();
        $sort->transactionIdDesc();

        $this->assertSame(['sort' => ['transaction_id' => 'desc']], $sort->toParams());
    }

    public function testDateDescSortsCorrectly(): void
    {
        $sort = new AffiliateCommissionSort();
        $sort->dateDesc();

        $this->assertSame(['sort' => ['date' => 'desc']], $sort->toParams());
    }

    public function testCommissionAscSortsCorrectly(): void
    {
        $sort = new AffiliateCommissionSort();
        $sort->commissionAsc();

        $this->assertSame(['sort' => ['commission' => 'asc']], $sort->toParams());
    }

    public function testSaleAscSortsCorrectly(): void
    {
        $sort = new AffiliateCommissionSort();
        $sort->saleAsc();

        $this->assertSame(['sort' => ['sale' => 'asc']], $sort->toParams());
    }

    public function testUsernameDescSortsCorrectly(): void
    {
        $sort = new AffiliateCommissionSort();
        $sort->usernameDesc();

        $this->assertSame(['sort' => ['username' => 'desc']], $sort->toParams());
    }

    public function testUpdatedAscSortsCorrectly(): void
    {
        $sort = new AffiliateCommissionSort();
        $sort->updatedAsc();

        $this->assertSame(['sort' => ['updated' => 'asc']], $sort->toParams());
    }

    public function testTypeDescSortsCorrectly(): void
    {
        $sort = new AffiliateCommissionSort();
        $sort->typeDesc();

        $this->assertSame(['sort' => ['type' => 'desc']], $sort->toParams());
    }

    public function testChainingMultipleSortFields(): void
    {
        $sort = new AffiliateCommissionSort();
        $sort->transactionIdAsc()->dateDesc()->typeAsc();

        $this->assertSame([
            'sort' => [
                'transaction_id' => 'asc',
                'date'           => 'desc',
                'type'           => 'asc',
            ],
        ], $sort->toParams());
    }

    public function testToParamsReturnsEmptySortWhenNothingSet(): void
    {
        $sort = new AffiliateCommissionSort();

        $this->assertSame(['sort' => []], $sort->toParams());
    }
}
