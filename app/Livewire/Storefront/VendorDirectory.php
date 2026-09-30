<?php

namespace App\Livewire\Storefront;

use App\Models\Vendor;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The brand directory — every approved Nigerian brand selling on HanbellShop.
 */
#[Layout('layouts.app')]
class VendorDirectory extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'featured')]
    public string $sort = 'featured';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Vendor::query()
            ->approved()
            ->withCount(['products' => fn ($q) => $q->published()])
            // A brand with nothing to sell is not worth a directory entry.
            ->whereHas('products', fn ($q) => $q->published());

        if (filled($this->search)) {
            $query->search($this->search);
        }

        $query = match ($this->sort) {
            'name' => $query->orderBy('name'),
            'products' => $query->orderByDesc('products_count'),
            'newest' => $query->orderByDesc('approved_at'),
            default => $query->orderByDesc('is_featured')->orderByDesc('products_count'),
        };

        return view('livewire.storefront.vendor-directory', [
            'vendors' => $query->paginate(24),
            'seo' => app(Seo::class)
                ->title(__('hanbell.vendor.directory_title'))
                ->description(__('hanbell.vendor.directory_subtitle'))
                ->schema([
                    '@type' => 'CollectionPage',
                    'name' => __('hanbell.vendor.directory_title'),
                    'description' => __('hanbell.vendor.directory_subtitle'),
                ]),
        ]);
    }
}
