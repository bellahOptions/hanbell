<?php

namespace App\Enums;

enum Gender: string
{
    case Women = 'women';
    case Men = 'men';
    case Unisex = 'unisex';
    case Kids = 'kids';

    public function label(): string
    {
        return match ($this) {
            self::Women => 'Women',
            self::Men => 'Men',
            self::Unisex => 'Unisex',
            self::Kids => 'Kids',
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
