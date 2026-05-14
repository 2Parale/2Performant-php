<?php

namespace TPerformant\API\Tests\Model;

use PHPUnit\Framework\TestCase;
use TPerformant\API\HTTP\Advertiser;
use TPerformant\API\Model\AdvertiserCommission;

class AdvertiserCommissionUpdateSaleTest extends TestCase
{
    private function createCommissionWithMockRequester(
        int $commissionId,
        Advertiser $mockAdvertiser
    ): AdvertiserCommission {
        $commission = new AdvertiserCommission(
            (object) ['id' => $commissionId],
            $mockAdvertiser
        );

        return $commission;
    }

    public function testUpdateSaleDelegatesToRequester(): void
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser
            ->expects($this->once())
            ->method('updateSaleCommission')
            ->with(123, '200.00', 'EUR', 'Price correction')
            ->willReturn('mocked_result');

        $commission = $this->createCommissionWithMockRequester(123, $advertiser);

        $result = $commission->updateSale('200.00', 'EUR', 'Price correction');
        $this->assertSame('mocked_result', $result);
    }

    public function testUpdateSalePassesCommissionIdFromModel(): void
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser
            ->expects($this->once())
            ->method('updateSaleCommission')
            ->with(
                $this->identicalTo(456),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $commission = $this->createCommissionWithMockRequester(456, $advertiser);
        $commission->updateSale('100', 'USD', 'test');
    }

    public function testUpdateSaleForwardsAmountCorrectly(): void
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser
            ->expects($this->once())
            ->method('updateSaleCommission')
            ->with(
                $this->anything(),
                $this->identicalTo('999.99'),
                $this->anything(),
                $this->anything()
            );

        $commission = $this->createCommissionWithMockRequester(1, $advertiser);
        $commission->updateSale('999.99', 'EUR', 'large amount');
    }

    public function testUpdateSaleForwardsCurrencyCodeCorrectly(): void
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser
            ->expects($this->once())
            ->method('updateSaleCommission')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->identicalTo('GBP'),
                $this->anything()
            );

        $commission = $this->createCommissionWithMockRequester(1, $advertiser);
        $commission->updateSale('50', 'GBP', 'currency test');
    }

    public function testUpdateSaleForwardsReasonCorrectly(): void
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser
            ->expects($this->once())
            ->method('updateSaleCommission')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->identicalTo('Partial refund applied')
            );

        $commission = $this->createCommissionWithMockRequester(1, $advertiser);
        $commission->updateSale('75.00', 'EUR', 'Partial refund applied');
    }
}
