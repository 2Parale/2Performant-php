<?php

namespace TPerformant\API\Tests\HTTP;

use PHPUnit\Framework\TestCase;
use TPerformant\API\Exception\TPException;
use TPerformant\API\HTTP\Advertiser;

class AdvertiserCreateCommissionTest extends TestCase
{

    /** @dataProvider invalidAffiliateProvider */
    public function testCreateCommissionRejectsInvalidAffiliate($affiliate): void
    {
        $this->expectException(TPException::class);
        $this->expectExceptionMessage('First parameter must be an affiliate ID or an Affiliate object');

        // Use partial mock or a test subclass if createMock blocks calling the real method
        $advertiser = $this->getMockBuilder(Advertiser::class)
            ->disableOriginalConstructor()
            ->onlyMethods([]) // or mock createCommission's dependencies
            ->getMock();
        $advertiser->createCommission($affiliate, 10.0, 'desc');
    }

    public static function invalidAffiliateProvider(): array
    {
        return [
            'non-numeric string' => ['not-an-id'],
            'object without affiliate type' => [new \stdClass()],
        ];
    }
}
