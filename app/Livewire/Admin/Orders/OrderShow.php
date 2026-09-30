<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\OrderStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Order;
use App\Services\Commerce\OrderService;
use App\Services\Security\AuditLogger;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Admin order detail.
 *
 * Status changes go through OrderStatus::canTransitionTo(), so an operator
 * cannot move a delivered order back to pending or skip payment entirely. Every
 * change is audited — money movements must be explainable after the fact.
 */
#[Layout('layouts.admin')]
class OrderShow extends Component
{
    use InteractsWithToasts;

    public int $orderId;

    public string $trackingNumber = '';

    public string $courier = '';

    public function mount(Order $order): void
    {
        $this->orderId = $order->id;
        $this->trackingNumber = (string) $order->vendorOrders()->value('tracking_number');
        $this->courier = (string) $order->vendorOrders()->value('courier');
    }

    public function updateStatus(string $status, OrderService $orders, AuditLogger $audit): void
    {
        $order = Order::find($this->orderId);
        $next = OrderStatus::tryFrom($status);

        if (! $order || ! $next) {
            return;
        }

        // Marking paid by hand is the one transition that must go through the
        // order service, because it is what actually commits stock.
        if ($next === OrderStatus::Paid && ! $order->isPaid()) {
            $orders->markPaid($order);
            $audit->log('order.marked_paid', $order, 'Order marked paid manually', ['order' => $order->number]);

            $this->toastSuccess($order->number.' marked as paid.');

            return;
        }

        if (! $order->transitionTo($next)) {
            $this->toastError('That status change is not allowed from '.$order->status->label().'.');

            return;
        }

        $audit->log('order.status_changed', $order, 'Order status changed to '.$next->label(), [
            'order' => $order->number,
            'status' => $next->value,
        ]);

        $this->toastSuccess($order->number.' is now '.$next->label().'.');
    }

    public function saveTracking(AuditLogger $audit): void
    {
        $order = Order::find($this->orderId);

        if (! $order) {
            return;
        }

        $order->vendorOrders()->update([
            'tracking_number' => $this->trackingNumber ?: null,
            'courier' => $this->courier ?: null,
            'updated_at' => now(),
        ]);

        $audit->log('order.tracking_updated', $order, 'Tracking details updated', [
            'order' => $order->number,
            'tracking' => $this->trackingNumber,
        ]);

        $this->toastSuccess(__('hanbell.admin.saved'));
    }

    public function render(): View
    {
        // `vendorOrders.items` is eager-loaded because the documents panel renders
        // a statement link per brand and the fulfilment block reads each
        // sub-order's lines. Each item's own `order` is loaded too: OrderItem
        // formats its money through $item->order->currency.
        $order = Order::with([
            'items.vendor',
            'items.order',
            'vendorOrders.vendor',
            'vendorOrders.order',
            'vendorOrders.items.order',
            'payments',
            'coupon',
            'user',
        ])->findOrFail($this->orderId);

        return view('livewire.admin.orders.order-show', [
            'order' => $order,
            'nextStatuses' => collect(OrderStatus::cases())
                ->filter(fn (OrderStatus $status) => $order->status->canTransitionTo($status))
                ->values(),
            'seo' => app(Seo::class)->title($order->number)->noindex(),
        ]);
    }
}