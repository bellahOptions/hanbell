<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\Security\AuditLogger;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Product moderation.
 *
 * The moderation queue is the operator's core loop: approve or reject what
 * brands submit. Every decision writes an audit row and returns a toast, and a
 * rejection requires a reason — a vendor cannot fix a problem they cannot see.
 */
#[Layout('layouts.admin')]
class ProductIndex extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $vendor = '';

    #[Url(except: 'newest')]
    public string $sort = 'newest';

    public int $perPage = 20;

    /** Rejection reason, keyed by product id, for the inline reject form. */
    public array $rejectReasons = [];

    public ?int $rejectingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedVendor(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    /* ------------------------------------------------------------------ *
     * Moderation actions
     * ------------------------------------------------------------------ */

    public function approve(int $productId, AuditLogger $audit): void
    {
        $product = Product::find($productId);

        if (! $product) {
            return;
        }

        $product->publish();

        $audit->moderation('product.approved', $product, 'Product approved and published', [
            'product' => $product->name,
            'vendor' => $product->vendor?->name,
        ]);

        $this->toastSuccess($product->name.' is now live.');
    }

    public function startReject(int $productId): void
    {
        $this->rejectingId = $productId;
        $this->rejectReasons[$productId] ??= '';
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
    }

    public function reject(int $productId, AuditLogger $audit): void
    {
        $reason = trim((string) ($this->rejectReasons[$productId] ?? ''));

        if ($reason === '') {
            $this->toastError('Please give a reason so the brand knows what to fix.');

            return;
        }

        $product = Product::find($productId);

        if (! $product) {
            return;
        }

        $product->reject($reason);

        $audit->moderation('product.rejected', $product, 'Product rejected', [
            'product' => $product->name,
            'reason' => $reason,
        ]);

        $this->rejectingId = null;
        $this->toastSuccess($product->name.' was rejected.');
    }

    public function unpublish(int $productId, AuditLogger $audit): void
    {
        $product = Product::find($productId);

        if (! $product) {
            return;
        }

        $product->forceFill(['status' => ProductStatus::Unpublished])->save();

        $audit->moderation('product.unpublished', $product, 'Product unpublished', ['product' => $product->name]);

        $this->toastInfo($product->name.' was unpublished.');
    }

    public function toggleFeatured(int $productId): void
    {
        $product = Product::find($productId);

        if (! $product) {
            return;
        }

        $product->forceFill(['is_featured' => ! $product->is_featured])->save();

        $this->toastSuccess(
            $product->is_featured
                ? $product->name.' is now featured.'
                : $product->name.' is no longer featured.'
        );
    }

    /* ------------------------------------------------------------------ */

    public function render(): View
    {
        return view('livewire.admin.products.product-index', [
            'products' => $this->query()->paginate($this->perPage),
            'vendors' => Vendor::orderBy('name')->get(['id', 'name']),
            'counts' => $this->statusCounts(),
            'seo' => app(Seo::class)->title(__('hanbell.admin.products'))->noindex(),
        ]);
    }

    private function query(): Builder
    {
        return Product::query()
            ->with(['vendor:id,name', 'category:id,name', 'media' => fn ($q) => $q->orderByDesc('is_primary')->limit(1)])
            ->withCount('variants')
            ->when(filled($this->search), fn (Builder $q) => $q->search($this->search))
            ->when(filled($this->status), fn (Builder $q) => $q->where('status', $this->status))
            ->when(filled($this->vendor), fn (Builder $q) => $q->where('vendor_id', $this->vendor))
            ->when($this->sort === 'price_high', fn (Builder $q) => $q->orderByDesc('price_minor'))
            ->when($this->sort === 'price_low', fn (Builder $q) => $q->orderBy('price_minor'))
            ->when($this->sort === 'name', fn (Builder $q) => $q->orderBy('name'))
            ->when($this->sort === 'oldest', fn (Builder $q) => $q->oldest())
            ->when($this->sort === 'newest', fn (Builder $q) => $q->latest('id'));
    }

    /** @return array<string,int> */
    private function statusCounts(): array
    {
        $counts = Product::query()
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->pluck('aggregate', 'status')
            ->all();

        $all = Product::count();

        return ['all' => $all] + array_map('intval', $counts);
    }
}
