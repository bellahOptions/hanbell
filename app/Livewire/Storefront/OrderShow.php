<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Order;
use App\Services\Commerce\OrderService;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Order detail.
 *
 * Reachable two ways: by a signed-in owner (checked against user_id) or by
 * anyone holding the order's opaque, expiring token (guest checkout). Both
 * paths are ownership-checked — a mismatch is a 404, not a 403, so the page
 * never confirms that an order number exists.
 */
#[Layout('layouts.app')]
class OrderShow extends Component
{
    use InteractsWithToasts;

    public ?int $orderId = null;

    public function mount(string $order): void
    {
        $this->orderId = $this->authorisedOrder($order)?->id;

        if ($this->orderId === null) {
            abort(404);
        }
    }

    private function authorisedOrder(string $token): ?Order
    {
        $resolved = (new Order)->resolveRouteBinding($token);

        if (! $resolved) {
            return null;
        }

        // A guest order (user_id null) is protected by the token alone.
        if ($resolved->user_id !== null && $resolved->user_id !== auth()->id()) {
            return null;
        }

        return $resolved;
    }

    public function cancel(OrderService $orders): void
    {
        $order = Order::find($this->orderId);

        if (! $order || ! $order->isCancellable()) {
            $this->toastError(__('hanbell.toast.generic_error'));

            return;
        }

        try {
            $orders->cancel($order, 'Cancelled by customer');
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $this->toastSuccess(__('hanbell.order.cancelled'));
    }

    public function render(): View
    {
        $order = Order::with(['items', 'vendorOrders.vendor', 'payments', 'coupon'])->find($this->orderId);

        if (! $order) {
            abort(404);
        }

        return view('livewire.storefront.order-show', [
            'order' => $order,
            'seo' => app(Seo::class)
                ->title(__('hanbell.order.order').' '.$order->number)
                ->noindex(),
        ]);
    }
}