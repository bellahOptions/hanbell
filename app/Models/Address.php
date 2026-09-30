<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id', 'label', 'recipient_name', 'phone', 'line1', 'line2',
        'city', 'state', 'postal_code', 'country', 'is_default',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function singleLine(): string
    {
        return collect([$this->line1, $this->line2, $this->city, $this->state, $this->postal_code])
            ->filter()
            ->implode(', ');
    }

    /** Promote this address to the user's default, demoting any other. */
    public function makeDefault(): void
    {
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->forceFill(['is_default' => true])->save();
    }
}
