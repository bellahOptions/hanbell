<?php

namespace Tests\Unit\Support;

use App\Support\Money;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    #[Test]
    public function it_formats_minor_units_as_naira(): void
    {
        $this->assertSame('₦12,500.00', Money::format(1250000));
        $this->assertSame('₦0.00', Money::format(0));
        $this->assertSame('₦12,500', Money::format(1250000, withDecimals: false));
    }

    #[Test]
    public function it_formats_other_currencies(): void
    {
        $this->assertSame('$125.50', Money::format(12550, 'USD'));
        $this->assertSame('£99.00', Money::format(9900, 'GBP'));
        $this->assertSame('€1,000.00', Money::format(100000, 'EUR'));
    }

    #[Test]
    public function it_converts_between_minor_and_major_units(): void
    {
        $this->assertSame(1250000, Money::toMinor(12500));
        $this->assertSame(12500.0, Money::toMajor(1250000));
        $this->assertSame('12500.00', Money::toGatewayAmount(1250000));
    }

    #[Test]
    public function it_handles_zero_decimal_currencies(): void
    {
        $this->assertSame(0, Money::exponent('JPY'));
        $this->assertSame(500, Money::toMinor(500, 'JPY'));
    }

    #[Test]
    public function compact_format_is_readable(): void
    {
        $this->assertSame('₦1.2M', Money::compact(125000000));
        $this->assertSame('₦845K', Money::compact(84500000));
        $this->assertSame('₦500.00', Money::compact(50000));
        $this->assertSame('₦2.5B', Money::compact(250000000000));
    }

    #[Test]
    public function compact_format_never_overstates_an_amount(): void
    {
        // Truncation, not rounding: ₦1,299,999 must not read as "₦1.3M",
        // because that claims more money than exists.
        $this->assertSame('₦1.2M', Money::compact(129999900));
        $this->assertSame('₦999K', Money::compact(99999900));
    }

    #[Test]
    public function percent_of_rounds_to_the_nearest_minor_unit(): void
    {
        $this->assertSame(15000, Money::percentOf(125000, 12));
        $this->assertSame(0, Money::percentOf(0, 12));
    }

    #[Test]
    public function allocate_never_loses_or_invents_money(): void
    {
        // ₦100 split three ways cannot be done with naive rounding.
        $parts = Money::allocate(10000, [1, 1, 1]);

        $this->assertCount(3, $parts);
        $this->assertSame(10000, array_sum($parts), 'Parts must reconcile to the total.');
    }

    #[Test]
    public function allocate_respects_uneven_weights_and_still_reconciles(): void
    {
        $parts = Money::allocate(100000, [70, 20, 10]);

        $this->assertSame(100000, array_sum($parts));
        $this->assertSame(70000, $parts[0]);
        $this->assertSame(20000, $parts[1]);
        $this->assertSame(10000, $parts[2]);
    }

    #[Test]
    public function allocate_handles_a_zero_weight_set(): void
    {
        $parts = Money::allocate(5000, [0, 0]);

        $this->assertSame(0, array_sum($parts));
    }

    #[Test]
    public function it_converts_between_currencies_using_supplied_rates(): void
    {
        $rates = ['NGN_USD' => 0.00065];

        // ₦100,000 → $65.00
        $this->assertSame(6500, Money::convert(10000000, 'NGN', 'USD', $rates));
    }

    #[Test]
    public function conversion_without_a_rate_returns_the_original_amount(): void
    {
        $this->assertSame(1000, Money::convert(1000, 'NGN', 'EUR', []));
        $this->assertSame(1000, Money::convert(1000, 'NGN', 'NGN', []));
    }

    #[Test]
    public function it_exposes_numeric_precision_for_inputs(): void
    {
        $this->assertSame('0.01', Money::step('NGN'));
        $this->assertSame('1', Money::step('JPY'));
    }
}
