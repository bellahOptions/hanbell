<?php

namespace App\Enums;

enum AdCampaignStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Paused => 'Paused',
            self::Completed => 'Completed',
            self::Archived => 'Archived',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-ink-100 text-ink-700 ring-ink-200',
            self::Active => 'bg-brand-50 text-brand-700 ring-brand-600/25',
            self::Paused => 'bg-warning-50 text-warning-600 ring-warning-500/25',
            self::Completed => 'bg-info-50 text-info-600 ring-info-500/25',
            self::Archived => 'bg-ink-900 text-white ring-ink-900',
        };
    }

    public function hex(): string
    {
        return match ($this) {
            self::Draft => '#9d9d96',
            self::Active => '#178508',
            self::Paused => '#f59e0b',
            self::Completed => '#3b82f6',
            self::Archived => '#171715',
        };
    }

    /** Only Active campaigns are ever served. */
    public function isServable(): bool
    {
        return $this === self::Active;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
