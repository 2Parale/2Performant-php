<?php

namespace TPerformant\API\Tests\Model;

use PHPUnit\Framework\TestCase;
use TPerformant\API\Filter\AffiliateProductFilter;
use TPerformant\API\Filter\AffiliateProductSort;
use TPerformant\API\HTTP\Affiliate as HttpAffiliate;
use TPerformant\API\Model\ProductFeed;
use TPerformant\API\Model\Program;

class ProductFeedTest extends TestCase
{
    private function createFeed(array $overrides = []): ProductFeed
    {
        $defaults = [
            'id'             => 1,
            'updated_at'     => '2024-06-01T12:00:00Z',
            'help'           => 'Feed help text',
            'products_count' => 150,
            'name'           => 'Main Product Feed',
        ];

        return new ProductFeed((object) array_merge($defaults, $overrides));
    }

    // -------------------------------------------------------------------------
    // classMap: program → Program instance
    // -------------------------------------------------------------------------

    public function testClassMapHydratesProgramAsModelInstance(): void
    {
        $feed = new ProductFeed((object)[
            'id'      => 1,
            'program' => (object)['id' => 5, 'name' => 'Demo'],
        ]);

        $this->assertInstanceOf(Program::class, $feed->getProgram());
    }

    public function testClassMapPassesProgramFieldsCorrectly(): void
    {
        $feed = new ProductFeed((object)[
            'id'      => 1,
            'program' => (object)['id' => 8, 'name' => 'Big Shop'],
        ]);

        $this->assertSame(8, $feed->getProgram()->getId());
        $this->assertSame('Big Shop', $feed->getProgram()->getName());
    }

    public function testProgramIsNullWhenNotInPayload(): void
    {
        $this->assertNull($this->createFeed()->getProgram());
    }

    // -------------------------------------------------------------------------
    // products() delegation to requester
    // -------------------------------------------------------------------------

    public function testProductsDelegatesToRequester(): void
    {
        $affiliate = $this->createMock(HttpAffiliate::class);
        $affiliate
            ->expects($this->once())
            ->method('getProducts')
            ->with(42, null, null)
            ->willReturn(['product_a', 'product_b']);

        $feed = new ProductFeed((object)['id' => 42], $affiliate);

        $result = $feed->products();
        $this->assertSame(['product_a', 'product_b'], $result);
    }

    public function testProductsDelegatesWithFilter(): void
    {
        $filter = new AffiliateProductFilter();

        $affiliate = $this->createMock(HttpAffiliate::class);
        $affiliate
            ->expects($this->once())
            ->method('getProducts')
            ->with(10, $this->identicalTo($filter), null)
            ->willReturn([]);

        $feed = new ProductFeed((object)['id' => 10], $affiliate);
        $feed->products($filter);
    }

    public function testProductsDelegatesWithFilterAndSort(): void
    {
        $filter = new AffiliateProductFilter();
        $sort   = new AffiliateProductSort();

        $affiliate = $this->createMock(HttpAffiliate::class);
        $affiliate
            ->expects($this->once())
            ->method('getProducts')
            ->with(15, $this->identicalTo($filter), $this->identicalTo($sort))
            ->willReturn([]);

        $feed = new ProductFeed((object)['id' => 15], $affiliate);
        $feed->products($filter, $sort);
    }

    public function testProductsPassesFeedIdFromModel(): void
    {
        $affiliate = $this->createMock(HttpAffiliate::class);
        $affiliate
            ->expects($this->once())
            ->method('getProducts')
            ->with($this->identicalTo(77), $this->anything(), $this->anything());

        $feed = new ProductFeed((object)['id' => 77], $affiliate);
        $feed->products();
    }
}
