<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired exactly once per order, when a verified payment converts a pending
 * order into a paid one.
 */
class OrderPaid
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Order $order) {}
}
