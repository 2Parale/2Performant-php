<?php

namespace TPerformant\API\Tests\Model;

use PHPUnit\Framework\TestCase;
use TPerformant\API\Model\Advertiser;
use TPerformant\API\Model\Affiliate;
use TPerformant\API\Model\User;

class UserModelTest extends TestCase
{
    public function testUserInfoIsNullByDefault(): void
    {
        $user = new User((object)['id' => 1]);
        $this->assertNull($user->getUserInfo());
    }

    // -------------------------------------------------------------------------
    // Affiliate subclass inherits all User fields
    // -------------------------------------------------------------------------

    public function testAffiliateInheritsIdFromUser(): void
    {
        $affiliate = new Affiliate((object)['id' => 10, 'email' => 'aff@example.com']);

        $this->assertSame(10, $affiliate->getId());
        $this->assertSame('aff@example.com', $affiliate->getEmail());
    }

    public function testAffiliateIsInstanceOfUser(): void
    {
        $affiliate = new Affiliate((object)['id' => 2]);

        $this->assertInstanceOf(User::class, $affiliate);
    }

    public function testAffiliateMapsUniqueCode(): void
    {
        $affiliate = new Affiliate((object)['unique_code' => 'AFF-UC']);

        $this->assertSame('AFF-UC', $affiliate->getUniqueCode());
    }

    // -------------------------------------------------------------------------
    // Advertiser subclass inherits all User fields
    // -------------------------------------------------------------------------

    public function testAdvertiserInheritsIdFromUser(): void
    {
        $advertiser = new Advertiser((object)['id' => 20, 'email' => 'adv@example.com']);

        $this->assertSame(20, $advertiser->getId());
        $this->assertSame('adv@example.com', $advertiser->getEmail());
    }

    public function testAdvertiserIsInstanceOfUser(): void
    {
        $advertiser = new Advertiser((object)['id' => 3]);

        $this->assertInstanceOf(User::class, $advertiser);
    }

    public function testAdvertiserMapsRole(): void
    {
        $advertiser = new Advertiser((object)['role' => 'advertiser']);

        $this->assertSame('advertiser', $advertiser->getRole());
    }
}
