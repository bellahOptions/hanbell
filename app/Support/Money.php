<?php

namespace App\Support;

/**
 * Money is stored as integer minor units (kobo) everywhere.
 *
 * Nothing in the application ever performs arithmetic on a float amount: the
 * only place a human-readable string is produced is Money::format(). This is
 * the single point where currency formatting rules live.
 */
final class Money
{
    /** Minor units per major unit, by currency. */
    private const EXPONENT = [
        'NGN' => 2, 'USD' => 2, 'GBP' => 2, 'EUR' => 2,
        'GHS' => 2, 'KES' => 2, 'ZAR' => 2, 'CAD' => 2, 'AUD' => 2,
        'JPY' => 0, 'KRW' => 0,
    ];

    private const SYMBOL = [
        'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€',
        'GHS' => 'GH₵', 'KES' => 'KSh', 'ZAR' => 'R', 'CAD' => 'C$', 'AUD' => 'A$',
        'JPY' => '¥',
    ];

    public static function exponent(string $currency): int
    {
        return self::EXPONENT[strtoupper($currency)] ?? 2;
    }

    public static function symbol(string $currency): string
    {
        return self::SYMBOL[strtoupper($currency)] ?? strtoupper($currency).' ';
    }

    /** Convert a minor-unit integer to its major-unit float (display only). */
    public static function toMajor(int $minor, string $currency = 'NGN'): float
    {
        return $minor / (10 ** self::exponent($currency));
    }

    /** Convert a major-unit value to minor units, rounding half-up. */
    public static function toMinor(int|float|string $major, string $currency = 'NGN'): int
    {
        return (int) round(((float) $major) * (10 ** self::exponent($currency)));
    }

    /**
     * Format minor units for display: "₦12,500.00".
     */
    public static function format(?int $minor, ?string $currency = null, bool $withDecimals = true): string
    {
        $currency = strtoupper($currency ?: config('hanbell.currency.default', 'NGN'));
        $minor ??= 0;

        $major = self::toMajor($minor, $currency);
        $decimals = self::exponent($currency);

        $formatted = number_format($major, $withDecimals ? $decimals : 0);

        return self::symbol($currency).$formatted;
    }

    /**
     * Compact format for KPI tiles and charts: "₦1.2M", "₦845K".
     *
     * Deliberately truncates rather than rounds. A KPI that rounds ₦1,250,000
     * up to "₦1.3M" overstates revenue by ₦50,000; shortening downward never
     * claims more money than actually exists.
     */
    public static function compact(?int $minor, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?: config('hanbell.currency.default', 'NGN'));
        $minor ??= 0;
        $major = self::toMajor($minor, $currency);

        $abs = abs($major);
        $sign = $major < 0 ? '-' : '';
        $symbol = self::symbol($currency);

        $truncate = fn (float $value, int $decimals): string => number_format(
            floor($value * (10 ** $decimals)) / (10 ** $decimals),
            $decimals,
        );

        return match (true) {
            $abs >= 1_000_000_000 => $sign.$symbol.$truncate($abs / 1_000_000_000, 1).'B',
            $abs >= 1_000_000 => $sign.$symbol.$truncate($abs / 1_000_000, 1).'M',
            $abs >= 1_000 => $sign.$symbol.number_format(floor($abs / 1_000)).'K',
            default => self::format($minor, $currency),
        };
    }

    /**
     * Machine-readable amount for payment APIs, e.g. "12500.00".
     * Gateways expect a decimal string in major units.
     */
    public static function toGatewayAmount(int $minor, string $currency = 'NGN'): string
    {
        return number_format(self::toMajor($minor, $currency), self::exponent($currency), '.', '');
    }

    /**
     * Percentage of an amount, rounded to the nearest minor unit.
     */
    public static function percentOf(int $minor, float $percent): int
    {
        return (int) round($minor * ($percent / 100));
    }

    /**
     * Split an amount into parts that sum exactly to the original.
     *
     * Naive per-part rounding loses or invents kobo (₦100 split three ways is
     * 33.33 + 33.33 + 33.33 = 99.99). The remainder is distributed one minor
     * unit at a time so the parts always reconcile to the total.
     *
     * @param  array<int,int|float>  $weights
     * @return array<int,int>
     */
    public static function allocate(int $total, array $weights): array
    {
        $sum = array_sum($weights);

        if ($sum <= 0 || $weights === []) {
            return array_fill(0, max(count($weights), 1), 0);
        }

        $result = [];
        $allocated = 0;

        foreach ($weights as $index => $weight) {
            $share = (int) floor($total * ($weight / $sum));
            $result[$index] = $share;
            $allocated += $share;
        }

        // Hand out the rounding remainder, largest fractional part first.
        $remainder = $total - $allocated;
        if ($remainder !== 0) {
            $fractions = [];
            foreach ($weights as $index => $weight) {
                $exact = $total * ($weight / $sum);
                $fractions[$index] = $exact - floor($exact);
            }
            arsort($fractions);

            $step = $remainder > 0 ? 1 : -1;
            $remaining = abs($remainder);
            foreach (array_keys($fractions) as $index) {
                if ($remaining === 0) {
                    break;
                }
                $result[$index] += $step;
                $remaining--;
            }
        }

        ksort($result);

        return array_values($result);
    }

    /**
     * Convert between currencies using a supplied rate table.
     * Rates are "1 unit of FROM = N units of TO".
     */
    public static function convert(int $minor, string $from, string $to, array $rates): int
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return $minor;
        }

        $rate = $rates[$from.'_'.$to] ?? null;

        if ($rate === null) {
            return $minor;
        }

        // Go via major units so differing exponents are handled correctly.
        $major = self::toMajor($minor, $from) * (float) $rate;

        return self::toMinor($major, $to);
    }

    /** Number of decimals for a currency, for input step attributes. */
    public static function step(string $currency = 'NGN'): string
    {
        $decimals = self::exponent($currency);

        return $decimals === 0 ? '1' : '0.'.str_repeat('0', $decimals - 1).'1';
    }
}
