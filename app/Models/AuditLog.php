<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasUuid;

    /** Append-only: an audit row is written once and never updated. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'event', 'subject_type', 'subject_id', 'description',
        'properties', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    public function scopeBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /** A short, human-readable label for the admin timeline. */
    public function label(): string
    {
        return $this->description ?: str($this->event)->replace(['.', '_'], ' ')->headline()->toString();
    }

    /** Colour key for the admin timeline dot. */
    public function tone(): string
    {
        return match (true) {
            str_starts_with($this->event, 'auth.login') => 'brand',
            str_starts_with($this->event, 'security.') => 'warning',
            str_starts_with($this->event, 'auth.') => 'info',
            str_contains($this->event, 'deleted'), str_contains($this->event, 'rejected') => 'danger',
            default => 'neutral',
        };
    }
}
