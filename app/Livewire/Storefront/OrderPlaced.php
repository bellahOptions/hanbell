<?php

namespace App\Livewire\Storefront;

use App\Models\Order;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Post-checkout confirmation landing page.
 */
#[Layout('layouts.app')]
class OrderPlaced extends Component
{
    public ?int $orderId = null;

    public function mount(string $order): void
    {
        $resolved = (new Order)->resolveRouteBinding($order);

        if (! $resolved) {
            abort(404);
        }

        if ($resolved->user_id !== null && $resolved->user_id !== auth()->id()) {
            abort(404);
        }

        $this->orderId = $resolved->id;
    }

    public function render(): View
    {
        $order = Order::with(['items', 'vendorOrders.vendor', 'payments'])->find($this->orderId);

        if (! $order) {
            abort(404);
        }

        return view('livewire.storefront.order-placed', [
            'order' => $order,
            'seo' => app(Seo::class)->title(__('hanbell.order.confirmation_title'))->noindex(),
        ]);
    }
}