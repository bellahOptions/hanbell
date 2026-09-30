<?php

namespace App\Enums;

enum PageStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-ink-100 text-ink-700 ring-ink-200',
            self::Published => 'bg-brand-50 text-brand-700 ring-brand-600/25',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
