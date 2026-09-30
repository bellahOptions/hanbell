<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per processed provider webhook.
 *
 * The unique (provider, event_id) index is what makes webhook handling
 * idempotent: a replayed event finds the existing row and returns without
 * touching order state, so a gateway retry can never double-charge stock or
 * send a second confirmation email.
 */
class PaymentWebhookEvent extends Model
{
    protected $fillable = ['provider', 'event_id', 'event_type', 'payload', 'processed_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function markProcessed(): void
    {
        $this->forceFill(['processed_at' => now()])->save();
    }
}
