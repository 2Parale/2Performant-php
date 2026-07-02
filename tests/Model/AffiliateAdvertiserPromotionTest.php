<?php

namespace TPerformant\API\Tests\Model;

use PHPUnit\Framework\TestCase;
use TPerformant\API\Model\AdvertiserPromotion;
use TPerformant\API\Model\AffiliateAdvertiserPromotion;
use TPerformant\API\Model\Program;

class AffiliateAdvertiserPromotionTest extends TestCase
{
    private function createPromotion(array $overrides = []): AffiliateAdvertiserPromotion
    {
        $defaults = [
            'id'                   => 1,
            'name'                 => 'Affiliate Summer Sale',
            'promotion_start'      => '2024-06-01',
            'promotion_end'        => '2024-08-31',
            'landing_page_link'    => 'https://shop.com/aff-summer',
            'affiliate_challenge'  => 'Sell 50 items',
            'affiliate_bonus'      => '25.00',
            'banners'              => [],
            'product_feeds'        => [],
            'linked_feeds'         => [],
            'shopping_event_id'    => 3,
            'shopping_event_name'  => 'Affiliate Event',
            'affrequest_status'    => 'accepted',
        ];

        return new AffiliateAdvertiserPromotion((object) array_merge($defaults, $overrides));
    }

    public function testIsInstanceOfAdvertiserPromotion(): void
    {
        $this->assertInstanceOf(AdvertiserPromotion::class, $this->createPromotion());
    }

    public function testInheritsProgramClassMap(): void
    {
        $promotion = new AffiliateAdvertiserPromotion((object)[
            'id'      => 1,
            'program' => (object)['id' => 8, 'name' => 'Partner Program'],
        ]);

        $this->assertInstanceOf(Program::class, $promotion->getProgram());
        $this->assertSame(8, $promotion->getProgram()->getId());
    }

    public function testMapsAffrequestStatus(): void
    {
        $this->assertSame('accepted', $this->createPromotion(['affrequest_status' => 'accepted'])->getAffrequestStatus());
    }

    public function testAffrequestStatusIsNullByDefault(): void
    {
        $promotion = new AffiliateAdvertiserPromotion((object)['id' => 1]);
        $this->assertNull($promotion->getAffrequestStatus());
    }
}
