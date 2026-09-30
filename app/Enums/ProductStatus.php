<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Published = 'published';
    case Unpublished = 'unpublished';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingReview => 'Pending review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Published => 'Published',
            self::Unpublished => 'Unpublished',
            self::Archived => 'Archived',
        };
    }

    /** Tailwind classes for admin badges. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-ink-100 text-ink-700 ring-ink-200',
            self::PendingReview => 'bg-warning-50 text-warning-600 ring-warning-500/25',
            self::Approved => 'bg-info-50 text-info-600 ring-info-500/25',
            self::Rejected => 'bg-danger-50 text-danger-600 ring-danger-500/25',
            self::Published => 'bg-brand-50 text-brand-700 ring-brand-600/25',
            self::Unpublished => 'bg-ink-100 text-ink-600 ring-ink-200',
            self::Archived => 'bg-ink-900 text-white ring-ink-900',
        };
    }

    /** Only these may be seen by shoppers. */
    public function isVisible(): bool
    {
        return $this === self::Published;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
