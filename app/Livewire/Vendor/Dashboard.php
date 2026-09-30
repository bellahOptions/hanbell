<?php

namespace App\Livewire\Vendor;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\VendorOrder;
use App\Support\Money;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The brand's own dashboard: catalogue health, stock alerts and payouts.
 *
 * Every query is scoped to the signed-in user's vendor record via vendor().
 * A vendor account with no vendor profile yet is sent to the application form
 * instead of seeing an empty shell.
 */
#[Layout('layouts.vendor')]
class Dashboard extends Component
{
    public function mount()
    {
        if (! auth()->user()->vendor()->exists()) {
            return $this->redirect(route('storefront.vendors.apply'));
        }

        return null;
    }

    public function render(): View
    {
        $vendor = auth()->user()->vendor;

        if (! $vendor) {
            return view('livewire.vendor.dashboard', [
                'vendor' => null,
                'seo' => app(Seo::class)->title(__('hanbell.vendor.dashboard'))->noindex(),
            ]);
        }

        $paidVendorOrders = VendorOrder::where('vendor_id', $vendor->id)
            ->whereHas('order', fn ($q) => $q->whereNotNull('paid_at'));

        $productIds = Product::where('vendor_id', $vendor->id)->pluck('id');

        return view('livewire.vendor.dashboard', [
            'vendor' => $vendor,
            'productCount' => $productIds->count(),
            'publishedCount' => Product::where('vendor_id', $vendor->id)->where('status', 'published')->count(),
            'pendingCount' => Product::where('vendor_id', $vendor->id)->where('status', 'pending_review')->count(),
            'grossMinor' => (int) (clone $paidVendorOrders)->sum('subtotal_minor'),
            'commissionMinor' => (int) (clone $paidVendorOrders)->sum('commission_minor'),
            'payoutMinor' => (int) (clone $paidVendorOrders)->sum('payout_minor'),
            'orderCount' => (clone $paidVendorOrders)->count(),
            'recentOrders' => VendorOrder::where('vendor_id', $vendor->id)
                ->with('order:id,number,customer_name,currency,created_at')
                ->latest()
                ->limit(6)
                ->get(),
            'lowStock' => Inventory::whereIn('product_id', $productIds)
                ->lowStock()
                ->with(['product:id,name', 'variant:id,name'])
                ->limit(8)
                ->get(),
            'seo' => app(Seo::class)->title(__('hanbell.vendor.dashboard'))->noindex(),
        ]);
    }
}