<?php

namespace App\Livewire\Storefront;

use App\Models\Order;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Guest order tracking.
 *
 * Both the order number AND the email must match. The order number appears on
 * receipts and in emails, so it is not a secret on its own — requiring the email
 * as well stops a leaked receipt becoming a window into someone's order.
 */
#[Layout('layouts.app')]
class OrderTracking extends Component
{
    public string $number = '';

    public string $email = '';

    public bool $searched = false;

    public function search(): void
    {
        $this->validate([
            'number' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $order = Order::query()
            ->where('number', trim($this->number))
            ->where('email', strtolower(trim($this->email)))
            ->first();

        $this->searched = true;

        if ($order) {
            $this->redirect(route('storefront.orders.show', ['order' => $order->urlToken()]));

            return;
        }

        $this->addError('number', __('hanbell.order.not_found'));
    }

    public function render(): View
    {
        return view('livewire.storefront.order-tracking', [
            'seo' => app(Seo::class)->title(__('hanbell.order.track_order'))->noindex(),
        ]);
    }
}
