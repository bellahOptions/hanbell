<?php

namespace App\Livewire\Vendor;

use App\Enums\Gender;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Category;
use App\Models\Product;
use App\Services\Security\AuditLogger;
use App\Support\Money;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Vendor product editor.
 *
 * A vendor may only ever write their own products: the vendor_id is taken from
 * the authenticated user and never from the form. New and edited products are
 * put back into `pending_review` — a vendor cannot publish straight to the
 * storefront.
 */
#[Layout('layouts.vendor')]
class ProductForm extends Component
{
    use InteractsWithToasts;
    use WithFileUploads;

    public ?int $productId = null;

    public ?int $category_id = null;

    public string $name = '';

    public string $summary = '';

    public string $description = '';

    public float $price = 0.0;

    public ?float $compare_at_price = null;

    public string $gender = 'unisex';

    public string $material = '';

    public string $made_in_city = '';

    public string $care_instructions = '';

    public int $stock = 0;

    /** @var array<int,mixed> */
    public array $images = [];

    public function mount(?Product $product = null): void
    {
        if (! $product || ! $product->exists) {
            return;
        }

        // Ownership is enforced before anything is loaded.
        if ($product->vendor_id !== auth()->user()->vendor?->id) {
            abort(404);
        }

        $this->productId = $product->id;
        $this->category_id = $product->category_id;
        $this->name = $product->name;
        $this->summary = (string) $product->summary;
        $this->description = (string) $product->description;
        $this->price = Money::toMajor((int) $product->price_minor, $product->currency);
        $this->compare_at_price = $product->compare_at_price_minor === null
            ? null
            : Money::toMajor((int) $product->compare_at_price_minor, $product->currency);
        $this->gender = $product->gender->value;
        $this->material = (string) $product->material;
        $this->made_in_city = (string) $product->made_in_city;
        $this->care_instructions = (string) $product->care_instructions;
        $this->stock = $product->availableQuantity();
    }

    public function save(AuditLogger $audit)
    {
        $vendor = auth()->user()->vendor;

        if (! $vendor) {
            abort(403);
        }

        $this->validate([
            'name' => 'required|string|max:180',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'summary' => 'nullable|string|max:500',
            'gender' => 'required|string',
            'images.*' => 'nullable|image|max:4096',
        ]);

        $attributes = [
            'vendor_id' => $vendor->id,
            'category_id' => $this->category_id ?: null,
            'name' => $this->name,
            'summary' => $this->summary,
            'description' => $this->description,
            'price_minor' => Money::toMinor($this->price),
            'compare_at_price_minor' => $this->compare_at_price === null ? null : Money::toMinor($this->compare_at_price),
            'gender' => $this->gender,
            'material' => $this->material ?: null,
            'made_in_city' => $this->made_in_city ?: null,
            'care_instructions' => $this->care_instructions ?: null,
            // Never published directly: a submission goes to the moderation queue.
            'status' => 'pending_review',
            'submitted_at' => now(),
        ];

        if ($this->productId) {
            $product = Product::where('vendor_id', $vendor->id)->findOrFail($this->productId);
            $product->update($attributes);
            $audit->log('product.vendor_updated', $product, 'Vendor updated a product', ['product' => $product->name]);
        } else {
            $product = Product::create($attributes);
            $audit->log('product.vendor_created', $product, 'Vendor created a product', ['product' => $product->name]);
        }

        foreach ($this->images as $index => $image) {
            $path = $image->store('products', 'public');

            $product->media()->create([
                'path' => $path,
                'disk' => 'public',
                'alt_text' => $product->name,
                'position' => $index,
                'is_primary' => $index === 0 && ! $product->media()->exists(),
            ]);
        }

        $this->toastSuccess('Saved and submitted for review.');

        return $this->redirect(route('vendor.products.index'));
    }

    public function render(): View
    {
        return view('livewire.vendor.product-form', [
            'product' => $this->productId ? Product::with('media')->find($this->productId) : null,
            'categories' => Category::orderBy('name')->pluck('name', 'id')->all(),
            'genders' => Gender::options(),
            'seo' => app(Seo::class)->title($this->productId ? 'Edit product' : 'New product')->noindex(),
        ]);
    }
}