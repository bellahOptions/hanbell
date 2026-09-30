<?php

namespace App\Livewire\Vendor;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\VendorOrder;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * A vendor's fulfilment queue: only their own slice of each order, with the
 * payout that slice earns — never the customer's whole basket or another
 * brand's takings.
 */
#[Layout('layouts.vendor')]
class OrderIndex extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    public string $filter = 'all';

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function markShipped(int $vendorOrderId): void
    {
        $vendorOrder = $this->owned($vendorOrderId);

        if (! $vendorOrder) {
            return;
        }

        $vendorOrder->markShipped();

        $this->toastSuccess($vendorOrder->number.' marked as shipped.');
    }

    public function markDelivered(int $vendorOrderId): void
    {
        $vendorOrder = $this->owned($vendorOrderId);

        if (! $vendorOrder) {
            return;
        }

        $vendorOrder->markDelivered();

        $this->toastSuccess($vendorOrder->number.' marked as delivered.');
    }

    private function owned(int $id): ?VendorOrder
    {
        return VendorOrder::where('vendor_id', auth()->user()->vendor?->id)->find($id);
    }

    public function render(): View
    {
        $vendorId = auth()->user()->vendor?->id;

        return view('livewire.vendor.order-index', [
            'orders' => VendorOrder::query()
                ->where('vendor_id', $vendorId)
                ->whereHas('order', fn ($q) => $q->whereNotNull('paid_at'))
                ->with(['order:id,number,customer_name,currency,paid_at', 'items'])
                ->when($this->filter !== 'all', fn ($q) => $q->where('status', $this->filter))
                ->latest()
                ->paginate(20),
            'seo' => app(Seo::class)->title(__('hanbell.admin.orders'))->noindex(),
        ]);
    }
}