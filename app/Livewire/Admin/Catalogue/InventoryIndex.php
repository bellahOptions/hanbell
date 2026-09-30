<?php

namespace App\Livewire\Admin\Catalogue;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Inventory;
use App\Models\Product;
use App\Services\Inventory\InventoryService;
use App\Services\Security\AuditLogger;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Stock control.
 *
 * Every adjustment goes through InventoryService, which locks the row and
 * writes an inventory_movements audit entry — so a stock figure can always be
 * explained afterwards.
 */
#[Layout('layouts.admin')]
class InventoryIndex extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $filter = '';

    /** New on-hand quantity, keyed by inventory id. */
    public array $quantities = [];

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'filter'], true)) {
            $this->resetPage();
        }
    }

    public function adjust(int $inventoryId, InventoryService $inventory, AuditLogger $audit): void
    {
        $record = Inventory::with('product')->find($inventoryId);

        if (! $record?->product) {
            return;
        }

        $newQuantity = (int) ($this->quantities[$inventoryId] ?? $record->quantity_on_hand);

        if ($newQuantity < 0) {
            $this->toastError('Stock cannot be negative.');

            return;
        }

        if ($newQuantity === $record->quantity_on_hand) {
            return;
        }

        $before = $record->quantity_on_hand;

        try {
            $inventory->adjustTo(
                $record->product,
                $record->variant,
                $newQuantity,
                auth()->id(),
                'Adjusted from the admin inventory screen',
            );
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $audit->log('inventory.adjusted', $record->product, 'Stock adjusted', [
            'product' => $record->product->name,
            'before' => $before,
            'after' => $newQuantity,
        ]);

        $this->toastSuccess($record->product->name.': '.$before.' → '.$newQuantity);
        unset($this->quantities[$inventoryId]);
    }

    public function restock(int $inventoryId, int $amount, InventoryService $inventory, AuditLogger $audit): void
    {
        $record = Inventory::with('product')->find($inventoryId);

        if (! $record?->product || $amount <= 0) {
            return;
        }

        try {
            $inventory->restock($record->product, $record->variant, $amount, null, auth()->id(), 'Quick restock');
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $audit->log('inventory.restocked', $record->product, "Restocked +{$amount}", ['product' => $record->product->name]);
        $this->toastSuccess('Added '.$amount.' to '.$record->product->name.'.');
    }

    public function render(): View
    {
        $query = Inventory::query()
            ->with(['product:id,name,vendor_id,currency', 'product.vendor:id,name', 'variant:id,name,product_id'])
            ->when(filled($this->search), fn (Builder $q) => $q->whereHas('product', fn (Builder $p) => $p->where('name', 'like', '%'.$this->search.'%')))
            ->when($this->filter === 'low', fn (Builder $q) => $q->lowStock())
            ->when($this->filter === 'out', fn (Builder $q) => $q->outOfStock())
            ->orderByRaw('quantity_on_hand - quantity_reserved ASC');

        return view('livewire.admin.catalogue.inventory-index', [
            'inventories' => $query->paginate(25),
            'lowCount' => Inventory::lowStock()->count(),
            'outCount' => Inventory::outOfStock()->count(),
            'totalUnits' => (int) Inventory::sum('quantity_on_hand'),
            'seo' => app(Seo::class)->title(__('hanbell.admin.inventory'))->noindex(),
        ]);
    }
}