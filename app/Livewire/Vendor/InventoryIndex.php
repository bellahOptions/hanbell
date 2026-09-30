<?php

namespace App\Livewire\Vendor;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Inventory;
use App\Models\Product;
use App\Services\Inventory\InventoryService;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.vendor')]
class InventoryIndex extends Component
{
    use InteractsWithToasts;

    /** inventory id => new on-hand quantity */
    public array $quantities = [];

    public function restock(int $inventoryId, int $amount, InventoryService $inventory): void
    {
        $record = $this->owned($inventoryId);

        if (! $record || $amount <= 0) {
            return;
        }

        try {
            $inventory->restock($record->product, $record->variant, $amount, null, auth()->id(), 'Vendor restock');
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $this->toastSuccess('Added '.$amount.' to '.$record->product->name.'.');
    }

    public function adjust(int $inventoryId, InventoryService $inventory): void
    {
        $record = $this->owned($inventoryId);

        if (! $record) {
            return;
        }

        $newQuantity = (int) ($this->quantities[$inventoryId] ?? $record->quantity_on_hand);

        try {
            $inventory->adjustTo($record->product, $record->variant, $newQuantity, auth()->id(), 'Vendor stock-take');
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return;
        }

        unset($this->quantities[$inventoryId]);
        $this->toastSuccess('Stock updated.');
    }

    /** Scoped to the signed-in vendor's own catalogue. */
    private function owned(int $inventoryId): ?Inventory
    {
        $productIds = Product::where('vendor_id', auth()->user()->vendor?->id)->pluck('id');

        return Inventory::with(['product', 'variant'])->whereIn('product_id', $productIds)->find($inventoryId);
    }

    public function render(): View
    {
        $productIds = Product::where('vendor_id', auth()->user()->vendor?->id)->pluck('id');

        return view('livewire.vendor.inventory-index', [
            'inventories' => Inventory::whereIn('product_id', $productIds)
                ->with(['product:id,name,currency', 'variant:id,name'])
                ->orderByRaw('quantity_on_hand - quantity_reserved ASC')
                ->get(),
            'seo' => app(Seo::class)->title(__('hanbell.admin.inventory'))->noindex(),
        ]);
    }
}