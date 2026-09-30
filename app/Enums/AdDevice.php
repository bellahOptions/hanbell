<?php

namespace App\Enums;

enum AdDevice: string
{
    case All = 'all';
    case Desktop = 'desktop';
    case Mobile = 'mobile';
    case Tablet = 'tablet';

    public function label(): string
    {
        return match ($this) {
            self::All => 'All devices',
            self::Desktop => 'Desktop',
            self::Mobile => 'Mobile',
            self::Tablet => 'Tablet',
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
