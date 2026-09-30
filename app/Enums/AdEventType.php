<?php

namespace App\Enums;

enum AdEventType: string
{
    case Impression = 'impression';
    case Click = 'click';
    case Conversion = 'conversion';

    public function label(): string
    {
        return match ($this) {
            self::Impression => 'Impression',
            self::Click => 'Click',
            self::Conversion => 'Conversion',
        };
    }

    /**
     * Impressions collapse inside a dedupe window; clicks and conversions
     * are never deduplicated.
     */
    public function isDeduplicable(): bool
    {
        return $this === self::Impression;
    }

    public function dedupeWindowMinutes(): int
    {
        return 30;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
