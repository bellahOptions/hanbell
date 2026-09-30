<?php

namespace App\Livewire\Vendor;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Product;
use App\Services\Security\AuditLogger;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * A vendor's own products.
 *
 * Hard-scoped to the signed-in user's vendor: there is no code path here that
 * accepts a vendor id from the client.
 */
#[Layout('layouts.vendor')]
class ProductIndex extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function submitForReview(int $id, AuditLogger $audit): void
    {
        $product = $this->ownedProduct($id);

        if (! $product) {
            return;
        }

        $product->submitForReview();

        $audit->log('product.submitted', $product, 'Product submitted for review', ['product' => $product->name]);
        $this->toastSuccess($product->name.' was submitted for review.');
    }

    public function render(): View
    {
        return view('livewire.vendor.product-index', [
            'products' => $this->query()->paginate(20),
            'seo' => app(Seo::class)->title(__('hanbell.admin.products'))->noindex(),
        ]);
    }

    private function query()
    {
        $vendor = auth()->user()->vendor;

        return Product::query()
            ->where('vendor_id', $vendor?->id ?? 0)
            ->with(['media' => fn ($q) => $q->orderByDesc('is_primary')->limit(1), 'category:id,name'])
            ->when(filled($this->search), fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->latest();
    }

    /** Scope every mutation to the signed-in vendor's own catalogue. */
    private function ownedProduct(int $id): ?Product
    {
        return Product::where('vendor_id', auth()->user()->vendor?->id)->find($id);
    }
}