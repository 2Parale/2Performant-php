<?php

namespace TPerformant\API\Tests\Model;

use PHPUnit\Framework\TestCase;
use TPerformant\API\Model\Sale;

class SaleTest extends TestCase
{
    private function createSale(array $overrides = []): Sale
    {
        $defaults = [
            'id' => 42,
            'amount' => '150.00',
            'currency_code' => 'EUR',
            'amount_in_working_currency' => '750.00',
            'working_currency_code' => 'RON',
        ];

        return new Sale((object) array_merge($defaults, $overrides));
    }

    public function testMapsIdFromApiResponse(): void
    {
        $sale = $this->createSale(['id' => 99]);
        $this->assertSame(99, $sale->getId());
    }

    public function testMapsAmountFromApiResponse(): void
    {
        $sale = $this->createSale(['amount' => '300.50']);
        $this->assertSame('300.50', $sale->getAmount());
    }

    public function testMapsCurrencyCodeFromApiResponse(): void
    {
        $sale = $this->createSale(['currency_code' => 'USD']);
        $this->assertSame('USD', $sale->getCurrencyCode());
    }

    public function testMapsAmountInWorkingCurrencyFromApiResponse(): void
    {
        $sale = $this->createSale(['amount_in_working_currency' => '1200.00']);
        $this->assertSame('1200.00', $sale->getAmountInWorkingCurrency());
    }

    public function testMapsWorkingCurrencyCodeFromApiResponse(): void
    {
        $sale = $this->createSale(['working_currency_code' => 'GBP']);
        $this->assertSame('GBP', $sale->getWorkingCurrencyCode());
    }

    public function testMapsAllFieldsTogether(): void
    {
        $sale = $this->createSale([
            'id' => 7,
            'amount' => '100.00',
            'currency_code' => 'CHF',
            'amount_in_working_currency' => '500.00',
            'working_currency_code' => 'RON',
        ]);

        $this->assertSame(7, $sale->getId());
        $this->assertSame('100.00', $sale->getAmount());
        $this->assertSame('CHF', $sale->getCurrencyCode());
        $this->assertSame('500.00', $sale->getAmountInWorkingCurrency());
        $this->assertSame('RON', $sale->getWorkingCurrencyCode());
    }

    public function testIgnoresUnknownFields(): void
    {
        $sale = new Sale((object) [
            'id' => 1,
            'amount' => '10.00',
            'currency_code' => 'EUR',
            'some_unknown_field' => 'should be ignored',
        ]);

        $this->assertSame(1, $sale->getId());
        $this->assertSame('10.00', $sale->getAmount());
    }

    public function testHandlesPartialData(): void
    {
        $sale = new Sale((object) ['id' => 5, 'amount' => '50.00']);

        $this->assertSame(5, $sale->getId());
        $this->assertSame('50.00', $sale->getAmount());
        $this->assertNull($sale->getCurrencyCode());
    }
}
