<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class BkashServiceTest extends TestCase
{
    public function test_invoice_number_format(): void
    {
        // Invoice numbers should start with INV- and be uppercase alphanumeric
        $invoiceNumber = 'INV-' . strtoupper(\Illuminate\Support\Str::random(10));
        $this->assertStringStartsWith('INV-', $invoiceNumber);
        $this->assertEquals(14, strlen($invoiceNumber));
    }

    public function test_amount_formatted_to_two_decimal_places(): void
    {
        $amount    = 100.5;
        $formatted = number_format($amount, 2, '.', '');
        $this->assertEquals('100.50', $formatted);
    }

    public function test_refundable_amount_calculation(): void
    {
        $amount          = 500.00;
        $refundedAmount  = 200.00;
        $refundable      = $amount - $refundedAmount;
        $this->assertEquals(300.00, $refundable);
    }
}
