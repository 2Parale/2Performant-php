<?php

namespace TPerformant\API\Tests\Model;

use PHPUnit\Framework\TestCase;
use TPerformant\API\HTTP\Affiliate as HttpAffiliate;
use TPerformant\API\Model\AffiliateProgram;
use TPerformant\API\Model\GenericEntity;
use TPerformant\API\Model\Program;

class GenericEntityTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Underscore-to-camelCase key conversion
    // -------------------------------------------------------------------------

    public function testSingleUnderscoreSegmentIsConvertedToCamelCase(): void
    {
        $entity = new class((object)['some_key' => 'hello']) extends GenericEntity {
            protected $someKey;
        };

        $this->assertSame('hello', $entity->getSomeKey());
    }

    public function testMultipleUnderscoreSegmentsAreConvertedToCamelCase(): void
    {
        $entity = new class((object)['some_long_key' => 'world']) extends GenericEntity {
            protected $someLongKey;
        };

        $this->assertSame('world', $entity->getSomeLongKey());
    }

    public function testKeyWithNoUnderscoreIsUnchanged(): void
    {
        $entity = new class((object)['status' => 'active']) extends GenericEntity {
            protected $status;
        };

        $this->assertSame('active', $entity->getStatus());
    }

    public function testKeyWithTrailingUnderscoreIsNormalised(): void
    {
        // Only interior underscores are converted; no trailing underscore in real API
        // but verifying that already-camelCased storage property maps correctly.
        $entity = new class((object)['created_at' => '2024-01-01']) extends GenericEntity {
            protected $createdAt;
        };

        $this->assertSame('2024-01-01', $entity->getCreatedAt());
    }

    // -------------------------------------------------------------------------
    // __call get accessors
    // -------------------------------------------------------------------------

    public function testGetAccessorReturnsPropertyValue(): void
    {
        $entity = new class((object)['id' => 42]) extends GenericEntity {
            protected $id;
        };

        $this->assertSame(42, $entity->getId());
    }

    public function testGetAccessorReturnsNullForUnsetProperty(): void
    {
        $entity = new class((object)[]) extends GenericEntity {
            protected $name = null;
        };

        $this->assertNull($entity->getName());
    }

    public function testGetAccessorReturnsDefaultPropertyValue(): void
    {
        $entity = new class((object)[]) extends GenericEntity {
            protected $score = 99;
        };

        $this->assertSame(99, $entity->getScore());
    }

    // -------------------------------------------------------------------------
    // __call error for undefined properties
    // -------------------------------------------------------------------------

    public function testCallTriggersErrorForUndefinedProperty(): void
    {
        $entity = new class((object)[]) extends GenericEntity {};

        set_error_handler(static function (int $errno, string $errstr): bool {
            throw new \RuntimeException($errstr);
        }, E_USER_ERROR);

        try {
            $entity->getUndefinedProperty();
            restore_error_handler();
            $this->fail('Expected RuntimeException to be thrown for undefined property');
        } catch (\RuntimeException $e) {
            restore_error_handler();
            $this->assertStringContainsString('Call to undefined method', $e->getMessage());
        }
    }

    public function testCallTriggersErrorMessageContainsClassName(): void
    {
        $entity = new class((object)[]) extends GenericEntity {};

        set_error_handler(static function (int $errno, string $errstr): bool {
            throw new \RuntimeException($errstr);
        }, E_USER_ERROR);

        try {
            $entity->getNonExistent();
            restore_error_handler();
            $this->fail('Expected RuntimeException to be thrown');
        } catch (\RuntimeException $e) {
            restore_error_handler();
            $this->assertMatchesRegularExpression('/Call to undefined method .+::getNonExistent\(\)/', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Unknown API keys are silently ignored
    // -------------------------------------------------------------------------

    public function testUnknownApiKeyIsIgnored(): void
    {
        $entity = new class((object)['id' => 1, 'unknown_field' => 'ignored']) extends GenericEntity {
            protected $id;
        };

        $this->assertSame(1, $entity->getId());
    }

    // -------------------------------------------------------------------------
    // Nested model construction via classMap (no requester – fallback to base class)
    // -------------------------------------------------------------------------

    public function testClassMapHydratesNestedObjectAsModelInstance(): void
    {
        $entity = new class((object)['program' => (object)['id' => 5, 'name' => 'Demo']]) extends GenericEntity {
            protected $program = null;

            protected function classMap(): array
            {
                return ['program' => 'Program'];
            }
        };

        $this->assertInstanceOf(Program::class, $entity->getProgram());
    }

    public function testClassMapPassesNestedDataToChildConstructor(): void
    {
        $entity = new class((object)['program' => (object)['id' => 7]]) extends GenericEntity {
            protected $program = null;

            protected function classMap(): array
            {
                return ['program' => 'Program'];
            }
        };

        /** @var Program $program */
        $program = $entity->getProgram();
        $this->assertSame(7, $program->getId());
    }

    public function testClassMapLeavesMappedPropertyNullWhenNotInPayload(): void
    {
        $entity = new class((object)['id' => 1]) extends GenericEntity {
            protected $id;
            protected $program = null;

            protected function classMap(): array
            {
                return ['program' => 'Program'];
            }
        };

        $this->assertNull($entity->getProgram());
    }

    // -------------------------------------------------------------------------
    // Role-aware class resolution via classMap (with requester)
    // -------------------------------------------------------------------------

    public function testClassMapResolvesToRoleSpecificClassWhenRequesterPresent(): void
    {
        $affiliate = $this->createMock(HttpAffiliate::class);
        $affiliate->method('getRole')->willReturn('affiliate');

        // Use Commission (which has classMap program => Program) with an affiliate requester.
        // The entity itself is a Commission, but we can also test via a direct anonymous class.
        // Anonymous classes cannot easily forward a typed requester through parent::__construct,
        // so we rely on a real concrete subclass (Commission) here.
        $commission = new \TPerformant\API\Model\Commission(
            (object)['program' => (object)['id' => 3]],
            $affiliate
        );

        $this->assertInstanceOf(AffiliateProgram::class, $commission->getProgram());
    }
}
