<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Succeeded => 'Succeeded',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
            self::PartiallyRefunded => 'Partially refunded',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-warning-50 text-warning-600 ring-warning-500/25',
            self::Processing => 'bg-info-50 text-info-600 ring-info-500/25',
            self::Succeeded => 'bg-brand-50 text-brand-700 ring-brand-600/25',
            self::Failed => 'bg-danger-50 text-danger-600 ring-danger-500/25',
            self::Cancelled => 'bg-ink-100 text-ink-600 ring-ink-200',
            self::Refunded, self::PartiallyRefunded => 'bg-ink-900 text-white ring-ink-900',
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::Succeeded;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
