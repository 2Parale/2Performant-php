<?php

namespace TPerformant\API\Tests\Filter;

use PHPUnit\Framework\TestCase;
use TPerformant\API\Filter\CollectionFilter;

class CollectionFilterTest extends TestCase
{
    private function makeFilter(array $fields = []): CollectionFilter
    {
        return new class($fields) extends CollectionFilter {
            private array $fields;
            public function __construct(array $fields) { $this->fields = $fields; }
            protected function filterableFields(): array { return $this->fields; }
        };
    }

    public function testPageSetsPageParam(): void
    {
        $filter = $this->makeFilter();
        $filter->page(3);

        $this->assertSame(['page' => 3], $filter->toParams());
    }

    public function testPerpageSetsPerPageParam(): void
    {
        $filter = $this->makeFilter();
        $filter->perpage(25);

        $this->assertSame(['perpage' => 25], $filter->toParams());
    }

    public function testToParamsReturnsEmptyArrayWhenNoFiltersApplied(): void
    {
        $filter = $this->makeFilter(['foo' => 'foo_field']);

        $this->assertSame([], $filter->toParams());
    }

    public function testCallDynamicDispatchMapsFieldToApiName(): void
    {
        $filter = $this->makeFilter(['myField' => 'my_field']);
        $filter->myField('some-value');

        $this->assertSame(['filter' => ['my_field' => 'some-value']], $filter->toParams());
    }

    public function testPageAndPerPageAndFilterAreAllIncludedInToParams(): void
    {
        $filter = $this->makeFilter(['status' => 'status']);
        $filter->page(2)->perpage(15)->status('active');

        $params = $filter->toParams();

        $this->assertSame(2, $params['page']);
        $this->assertSame(15, $params['perpage']);
        $this->assertSame(['status' => 'active'], $params['filter']);
    }

    public function testChainingReturnsSameInstance(): void
    {
        $filter = $this->makeFilter(['foo' => 'foo_field', 'bar' => 'bar_field']);
        $result = $filter->page(1)->perpage(10)->foo('x')->bar('y');

        $this->assertSame($filter, $result);
    }

    public function testCallOnUnknownFieldTriggersUserError(): void
    {
        $filter = $this->makeFilter([]);

        $errorTriggered = false;
        set_error_handler(function () use (&$errorTriggered): bool {
            $errorTriggered = true;
            return true;
        }, E_USER_ERROR);

        try {
            $filter->nonexistentField('value');
        } finally {
            restore_error_handler();
        }
        
        $this->assertTrue($errorTriggered);
    }
}
