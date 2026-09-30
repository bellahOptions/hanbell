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
 * A brand's own storefront. The Nigerian maker is the point of HanbellShop, so
 * their page gets a real identity — story, location, and their whole catalogue.
 */
#[Layout('layouts.app')]
class VendorShow extends Component
{
    use WithPagination;

    public ?int $vendorId = null;

    #[Url(except: 'newest')]
    public string $sort = 'newest';

    public function mount(string $vendor, ?string $slug = null): void
    {
        // An inline Livewire component never receives a route-bound model, so
        // the token is verified here with the same route binding the router
        // would have used.
        $resolved = (new Vendor)->resolveRouteBinding($vendor);

        if (! $resolved instanceof Vendor || ! $resolved->isApproved()) {
            abort(404);
        }

        $this->vendorId = $resolved->id;
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $vendor = Vendor::find($this->vendorId);

        if (! $vendor) {
            abort(404);
        }

        $products = $vendor->products()
            ->published()
            ->with([
                'vendor:id,name,slug,uuid,token_version,status',
                'media' => fn ($q) => $q->orderByDesc('is_primary')->limit(1),
                'inventories',
            ])
            ->when($this->sort === 'price_low', fn ($q) => $q->orderBy('price_minor'))
            ->when($this->sort === 'price_high', fn ($q) => $q->orderByDesc('price_minor'))
            ->when($this->sort === 'popular', fn ($q) => $q->orderByDesc('sales_count'))
            ->when($this->sort === 'newest', fn ($q) => $q->orderByDesc('published_at'))
            ->paginate(24);

        return view('livewire.storefront.vendor-show', [
            'vendor' => $vendor,
            'products' => $products,
            'seo' => app(Seo::class)
                ->title($vendor->name)
                ->description($vendor->description
                    ?: $vendor->name.' is a Nigerian fashion brand on HanbellShop, based in '.$vendor->location().'.')
                ->type('profile')
                ->schema([
                    '@type' => 'Store',
                    'name' => $vendor->name,
                    'description' => $vendor->description,
                    'url' => $vendor->publicUrl(),
                    'address' => [
                        '@type' => 'PostalAddress',
                        'addressLocality' => $vendor->city,
                        'addressRegion' => $vendor->state,
                        'addressCountry' => 'NG',
                    ],
                ]),
        ]);
    }
}
