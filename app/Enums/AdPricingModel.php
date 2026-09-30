<?php

namespace App\Enums;

enum AdPricingModel: string
{
    /** Cost per mille — advertiser pays per 1,000 viewable impressions. */
    case Cpm = 'cpm';
    /** Cost per click. */
    case Cpc = 'cpc';
    /** Flat fee for the whole flight, regardless of delivery. */
    case Flat = 'flat';

    public function label(): string
    {
        return match ($this) {
            self::Cpm => 'CPM (per 1,000 impressions)',
            self::Cpc => 'CPC (per click)',
            self::Flat => 'Flat fee (whole flight)',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
