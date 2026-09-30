<?php

namespace App\Livewire\Admin\Products;

use App\Enums\Gender;
use App\Enums\ProductStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Category;
use App\Models\Department;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\Security\AuditLogger;
use App\Support\Money;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Admin product create/edit.
 *
 * Prices are entered in major units (Naira) and converted to integer minor
 * units immediately — nothing in the database or in arithmetic ever sees a
 * float.
 */
#[Layout('layouts.admin')]
class ProductForm extends Component
{
    use InteractsWithToasts;

    public ?int $productId = null;

    public ?int $vendor_id = null;

    public ?int $category_id = null;

    public ?int $department_id = null;

    public string $name = '';

    public string $sku = '';

    public string $summary = '';

    public string $description = '';

    public float $price = 0.0;

    public ?float $compare_at_price = null;

    public string $status = 'draft';

    public string $gender = 'unisex';

    public string $material = '';

    public string $made_in_city = '';

    public string $care_instructions = '';

    public bool $is_featured = false;

    public bool $is_new_arrival = true;

    public bool $is_trending = false;

    public bool $is_handmade = true;

    public string $meta_title = '';

    public string $meta_description = '';

    public string $llm_summary = '';

    public bool $is_indexable = true;

    public function mount(?Product $product = null): void
    {
        if (! $product || ! $product->exists) {
            return;
        }

        $this->productId = $product->id;
        $this->vendor_id = $product->vendor_id;
        $this->category_id = $product->category_id;
        $this->department_id = $product->department_id;
        $this->name = $product->name;
        $this->sku = (string) $product->sku;
        $this->summary = (string) $product->summary;
        $this->description = (string) $product->description;
        $this->price = Money::toMajor((int) $product->price_minor, $product->currency);
        $this->compare_at_price = $product->compare_at_price_minor === null
            ? null
            : Money::toMajor((int) $product->compare_at_price_minor, $product->currency);
        $this->status = $product->status->value;
        $this->gender = $product->gender->value;
        $this->material = (string) $product->material;
        $this->made_in_city = (string) $product->made_in_city;
        $this->care_instructions = (string) $product->care_instructions;
        $this->is_featured = $product->is_featured;
        $this->is_new_arrival = $product->is_new_arrival;
        $this->is_trending = $product->is_trending;
        $this->is_handmade = $product->is_handmade;
        $this->meta_title = (string) $product->meta_title;
        $this->meta_description = (string) $product->meta_description;
        $this->llm_summary = (string) $product->llm_summary;
        $this->is_indexable = $product->is_indexable;
    }

    public function save(AuditLogger $audit)
    {
        $this->validate([
            'vendor_id' => 'required|integer|exists:vendors,id',
            'name' => 'required|string|max:180',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'status' => 'required|string',
            'gender' => 'required|string',
            'summary' => 'nullable|string|max:500',
        ]);

        $attributes = [
            'vendor_id' => $this->vendor_id,
            'category_id' => $this->category_id ?: null,
            'department_id' => $this->department_id ?: null,
            'name' => $this->name,
            'sku' => $this->sku ?: null,
            'summary' => $this->summary,
            'description' => $this->description,
            'price_minor' => Money::toMinor($this->price),
            'compare_at_price_minor' => $this->compare_at_price === null ? null : Money::toMinor($this->compare_at_price),
            'status' => $this->status,
            'gender' => $this->gender,
            'material' => $this->material ?: null,
            'made_in_city' => $this->made_in_city ?: null,
            'care_instructions' => $this->care_instructions ?: null,
            'is_featured' => $this->is_featured,
            'is_new_arrival' => $this->is_new_arrival,
            'is_trending' => $this->is_trending,
            'is_handmade' => $this->is_handmade,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'llm_summary' => $this->llm_summary ?: null,
            'is_indexable' => $this->is_indexable,
        ];

        if ($this->productId) {
            $product = Product::findOrFail($this->productId);
            $product->update($attributes);
            $audit->log('product.updated', $product, 'Product updated by an administrator', ['product' => $product->name]);
            $this->toastSuccess(__('hanbell.admin.saved'));
        } else {
            $product = Product::create($attributes);
            $audit->log('product.created', $product, 'Product created by an administrator', ['product' => $product->name]);
            $this->toastSuccess(__('hanbell.admin.created'));
        }

        return $this->redirect(route('admin.products.index'));
    }

    public function render(): View
    {
        return view('livewire.admin.products.product-form', [
            'product' => $this->productId ? Product::with('media')->find($this->productId) : null,
            'vendors' => Vendor::orderBy('name')->pluck('name', 'id')->all(),
            'categories' => Category::orderBy('name')->pluck('name', 'id')->all(),
            'departments' => Department::orderBy('name')->pluck('name', 'id')->all(),
            'statuses' => collect(ProductStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all(),
            'genders' => Gender::options(),
            'seo' => app(Seo::class)->title($this->productId ? 'Edit product' : 'New product')->noindex(),
        ]);
    }
}