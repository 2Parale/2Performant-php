<?php

namespace TPerformant\API\Tests\Model;

use PHPUnit\Framework\TestCase;
use TPerformant\API\Filter\AffiliateProductSort;
use TPerformant\API\HTTP\Affiliate as HttpAffiliate;
use TPerformant\API\Model\ProductFeed;

/**
 * Covers ProductFeed::products() delegation to the requester.
 *
 * Note: the no-arg, filter-only, filter+sort, and feed-ID cases are
 * already covered by ProductFeedTest. Only the sort-without-filter
 * combination is missing there.
 */
class ProductFeedProductsTest extends TestCase
{
    public function testProductsDelegatesWithSortAndNullFilter(): void
    {
        $sort = new AffiliateProductSort();

        $affiliate = $this->createMock(HttpAffiliate::class);
        $affiliate
            ->expects($this->once())
            ->method('getProducts')
            ->with(33, null, $this->identicalTo($sort))
            ->willReturn([]);

        $feed = new ProductFeed((object) ['id' => 33], $affiliate);
        $feed->products(null, $sort);
    }
}
