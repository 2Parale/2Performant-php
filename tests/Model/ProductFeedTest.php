<?php

namespace TPerformant\API\Tests\Model;

use PHPUnit\Framework\TestCase;
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
}
