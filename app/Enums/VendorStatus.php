<?php

namespace App\Enums;

enum VendorStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Suspended => 'Suspended',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-warning-50 text-warning-600 ring-warning-500/25',
            self::Approved => 'bg-brand-50 text-brand-700 ring-brand-600/25',
            self::Rejected => 'bg-danger-50 text-danger-600 ring-danger-500/25',
            self::Suspended => 'bg-ink-900 text-white ring-ink-900',
        };
    }

    public function canSell(): bool
    {
        return $this === self::Approved;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
