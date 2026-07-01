<?php

namespace TPerformant\API\Tests\Filter;

use PHPUnit\Framework\TestCase;
use TPerformant\API\Filter\CollectionSort;

class CollectionSortTest extends TestCase
{
    private function makeSort(array $fields = []): CollectionSort
    {
        return new class($fields) extends CollectionSort {
            private array $fields;
            public function __construct(array $fields) { $this->fields = $fields; }
            protected function sortableFields(): array { return $this->fields; }
        };
    }

    public function testAscDispatchMapsFieldToAscOrder(): void
    {
        $sort = $this->makeSort(['createdAt' => 'created_at']);
        $sort->createdAtAsc();

        $this->assertSame(['sort' => ['created_at' => 'asc']], $sort->toParams());
    }

    public function testDescDispatchMapsFieldToDescOrder(): void
    {
        $sort = $this->makeSort(['createdAt' => 'created_at']);
        $sort->createdAtDesc();

        $this->assertSame(['sort' => ['created_at' => 'desc']], $sort->toParams());
    }

    public function testToParamsReturnsEmptySortWhenNothingSet(): void
    {
        $sort = $this->makeSort(['foo' => 'foo_field']);

        $this->assertSame(['sort' => []], $sort->toParams());
    }

    public function testChainingReturnsSameInstance(): void
    {
        $sort = $this->makeSort(['foo' => 'foo_field', 'bar' => 'bar_field']);
        $result = $sort->fooAsc()->barDesc();

        $this->assertSame($sort, $result);
    }

    public function testChainingBuildsMultipleSortEntries(): void
    {
        $sort = $this->makeSort(['foo' => 'foo_field', 'bar' => 'bar_field']);
        $sort->fooAsc()->barDesc();

        $this->assertSame(['sort' => ['foo_field' => 'asc', 'bar_field' => 'desc']], $sort->toParams());
    }

    public function testCallOnUnknownSortFieldTriggersUserError(): void
    {
        $sort = $this->makeSort([]);

        $errorTriggered = false;
        set_error_handler(function () use (&$errorTriggered): bool {
            $errorTriggered = true;
            return true;
        }, E_USER_ERROR);

        try {
            $sort->nonexistentFieldAsc();
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($errorTriggered);
    }

    public function testCallWithoutAscOrDescSuffixTriggersUserError(): void
    {
        $sort = $this->makeSort(['createdAt' => 'created_at']);

        $errorTriggered = false;
        set_error_handler(function () use (&$errorTriggered): bool {
            $errorTriggered = true;
            return true;
        }, E_USER_ERROR);

        try {
            $sort->createdAt();
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($errorTriggered);
    }
}
