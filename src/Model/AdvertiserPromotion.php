<?php

namespace TPerformant\API\Model;

class AdvertiserPromotion extends GenericEntity {
    protected $id;
    protected $name;
    protected $promotionStart;
    protected $promotionEnd;
    protected $landingPageLink;
    protected $affiliateChallenge;
    protected $affiliateBonus;
    protected $banners;
    protected $productFeeds;
    protected $linkedFeeds;
    protected $shoppingEventId;
    protected $shoppingEventName;
    protected $program = null;

    /**
     * @inheritdoc
     */
    protected function classMap() {
        return array_merge(parent::classMap(), [
            'program' => 'Program'
        ]);
    }
}
