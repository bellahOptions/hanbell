<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    use HasUuid;

    protected $fillable = [
        'email', 'locale', 'is_active', 'source', 'confirmed_at', 'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNull('unsubscribed_at');
    }

    public static function subscribe(string $email, ?string $source = null): self
    {
        return static::updateOrCreate(
            ['email' => strtolower(trim($email))],
            [
                'is_active' => true,
                'locale' => app()->getLocale(),
                'source' => $source,
                'confirmed_at' => now(),
                'unsubscribed_at' => null,
            ],
        );
    }

    public function unsubscribe(): void
    {
        $this->forceFill([
            'is_active' => false,
            'unsubscribed_at' => now(),
        ])->save();
    }
}
