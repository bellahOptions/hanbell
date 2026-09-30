<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting payment',
            self::Paid => 'Paid',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-warning-50 text-warning-600 ring-warning-500/25',
            self::Paid => 'bg-brand-50 text-brand-700 ring-brand-600/25',
            self::Processing => 'bg-info-50 text-info-600 ring-info-500/25',
            self::Shipped => 'bg-accent-100 text-accent-800 ring-accent-500/30',
            self::Delivered => 'bg-brand-600 text-white ring-brand-700',
            self::Cancelled => 'bg-ink-100 text-ink-600 ring-ink-200',
            self::Refunded => 'bg-danger-50 text-danger-600 ring-danger-500/25',
        };
    }

    /** Colour used in admin charts / KPI tiles. */
    public function hex(): string
    {
        return match ($this) {
            self::Pending => '#f59e0b',
            self::Paid => '#178508',
            self::Processing => '#3b82f6',
            self::Shipped => '#eab308',
            self::Delivered => '#126b08',
            self::Cancelled => '#9d9d96',
            self::Refunded => '#ef4444',
        };
    }

    public function isFulfilled(): bool
    {
        return in_array($this, [self::Shipped, self::Delivered], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled, self::Refunded], true);
    }

    /** Allowed forward transitions. Guards admin state changes. */
    public function canTransitionTo(self $next): bool
    {
        return in_array($next, match ($this) {
            self::Pending => [self::Paid, self::Cancelled],
            self::Paid => [self::Processing, self::Shipped, self::Refunded, self::Cancelled],
            self::Processing => [self::Shipped, self::Refunded, self::Cancelled],
            self::Shipped => [self::Delivered, self::Refunded],
            self::Delivered => [self::Refunded],
            self::Cancelled, self::Refunded => [],
        }, true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
