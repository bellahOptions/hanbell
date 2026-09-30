<?php

namespace App\Livewire\Admin\Vendors;

use App\Models\Vendor;
use App\Support\Money;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class VendorShow extends Component
{
    public int $vendorId;

    public function mount(Vendor $vendor): void
    {
        $this->vendorId = $vendor->id;
    }

    public function render(): View
    {
        $vendor = Vendor::with(['owner', 'brands'])->withCount('products')->findOrFail($this->vendorId);

        $paidOrders = \App\Models\VendorOrder::where('vendor_id', $vendor->id)
            ->whereHas('order', fn ($q) => $q->whereNotNull('paid_at'));

        return view('livewire.admin.vendors.vendor-show', [
            'vendor' => $vendor,
            'grossMinor' => (int) (clone $paidOrders)->sum('subtotal_minor'),
            'commissionMinor' => (int) (clone $paidOrders)->sum('commission_minor'),
            'payoutMinor' => (int) (clone $paidOrders)->sum('payout_minor'),
            'orderCount' => (clone $paidOrders)->count(),
            'recentProducts' => $vendor->products()->with('media')->latest()->limit(8)->get(),
            'seo' => app(Seo::class)->title($vendor->name)->noindex(),
        ]);
    }
}