<?php

namespace TPerformant\API\Tests\Model;

use PHPUnit\Framework\TestCase;
use TPerformant\API\Exception\TPException;
use TPerformant\API\HTTP\Advertiser;
use TPerformant\API\Model\AdvertiserCommission;

class AdvertiserCommissionTest extends TestCase
{
    private function makeCommission(int $id, Advertiser $advertiser): AdvertiserCommission
    {
        return new AdvertiserCommission(
            (object) ['id' => $id],
            $advertiser
        );
    }

    // -------------------------------------------------------------------------
    // edit()
    // -------------------------------------------------------------------------

    public function testEditWithNumericAmountDelegatesCorrectly(): void
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser
            ->expects($this->once())
            ->method('editCommission')
            ->with(
                42,
                'Price fix',
                ['amount' => 15.5, 'currencyCode' => null],
                null
            )
            ->willReturn('mocked_result');

        $commission = $this->makeCommission(42, $advertiser);
        $result = $commission->edit('Price fix', 15.5);

        $this->assertSame('mocked_result', $result);
    }

    public function testEditWithArrayExtractsBothAmountAndCurrencyCode(): void
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser
            ->expects($this->once())
            ->method('editCommission')
            ->with(
                7,
                'Correction',
                ['amount' => 20.0, 'currencyCode' => 'RON'],
                null
            )
            ->willReturn('mocked_result');

        $commission = $this->makeCommission(7, $advertiser);
        $result = $commission->edit('Correction', ['amount' => 20.0, 'currencyCode' => 'RON']);

        $this->assertSame('mocked_result', $result);
    }

    public function testEditWithArrayMissingCurrencyCodeDefaultsToNull(): void
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser
            ->expects($this->once())
            ->method('editCommission')
            ->with(
                7,
                'No currency',
                ['amount' => 20.0, 'currencyCode' => null],
                null
            )
            ->willReturn('mocked_result');

        $commission = $this->makeCommission(7, $advertiser);
        $result = $commission->edit('No currency', ['amount' => 20.0]);

        $this->assertSame('mocked_result', $result);
    }

    public function testEditWithArrayMissingAmountThrowsTPException(): void
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser->expects($this->never())->method('editCommission');

        $commission = $this->makeCommission(1, $advertiser);

        $this->expectException(TPException::class);
        $commission->edit('reason', ['currencyCode' => 'EUR']);
    }

    public function testEditWithObjectExtractsBothAmountAndCurrencyCode(): void
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser
            ->expects($this->once())
            ->method('editCommission')
            ->with(
                9,
                'Object edit',
                ['amount' => 30.0, 'currencyCode' => 'USD'],
                null
            )
            ->willReturn('mocked_result');

        $commission = $this->makeCommission(9, $advertiser);
        $result = $commission->edit('Object edit', (object) ['amount' => 30.0, 'currencyCode' => 'USD']);

        $this->assertSame('mocked_result', $result);
    }

    public function testEditWithObjectMissingCurrencyCodeDefaultsToNull(): void
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser
            ->expects($this->once())
            ->method('editCommission')
            ->with(
                9,
                'No currency',
                ['amount' => 30.0, 'currencyCode' => null],
                null
            )
            ->willReturn('mocked_result');

        $commission = $this->makeCommission(9, $advertiser);
        $result = $commission->edit('No currency', (object) ['amount' => 30.0]);

        $this->assertSame('mocked_result', $result);
    }

    public function testEditWithObjectMissingAmountThrowsTPException(): void
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser->expects($this->never())->method('editCommission');

        $commission = $this->makeCommission(1, $advertiser);

        $this->expectException(TPException::class);
        $commission->edit('reason', (object) ['currencyCode' => 'EUR']);
    }

    public function testEditWithInvalidTypeStringThrowsTPException(): void
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser->expects($this->never())->method('editCommission');

        $commission = $this->makeCommission(1, $advertiser);

        $this->expectException(TPException::class);
        $commission->edit('reason', 'not-a-number');
    }

    // -------------------------------------------------------------------------
    // accept()
    // -------------------------------------------------------------------------

    public function testAcceptDelegatesWithCorrectIdAndReason(): void
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser
            ->expects($this->once())
            ->method('acceptCommission')
            ->with(55, 'Looks good')
            ->willReturn('accepted');

        $commission = $this->makeCommission(55, $advertiser);
        $result = $commission->accept('Looks good');

        $this->assertSame('accepted', $result);
    }

    // -------------------------------------------------------------------------
    // reject()
    // -------------------------------------------------------------------------

    public function testRejectDelegatesWithCorrectIdAndReason(): void
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser
            ->expects($this->once())
            ->method('rejectCommission')
            ->with(88, 'Fraudulent')
            ->willReturn('rejected');

        $commission = $this->makeCommission(88, $advertiser);
        $result = $commission->reject('Fraudulent');

        $this->assertSame('rejected', $result);
    }

    // -------------------------------------------------------------------------
    // updateSale()
    // -------------------------------------------------------------------------

    public function testUpdateSaleDelegatesWithCorrectArguments(): void
    {
        $advertiser = $this->createMock(Advertiser::class);
        $advertiser
            ->expects($this->once())
            ->method('updateSaleCommission')
            ->with(33, '200.00', 'EUR', 'Price correction')
            ->willReturn('sale_updated');

        $commission = $this->makeCommission(33, $advertiser);
        $result = $commission->updateSale('200.00', 'EUR', 'Price correction');

        $this->assertSame('sale_updated', $result);
    }
}
