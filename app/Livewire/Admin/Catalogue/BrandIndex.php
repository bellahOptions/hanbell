<?php

namespace App\Livewire\Admin\Catalogue;

use App\Livewire\Admin\ResourceIndex;
use App\Models\Brand;
use App\Models\Vendor;

class BrandIndex extends ResourceIndex
{
    protected array $validationRules = [
        'form.name' => 'required|string|max:120',
        'form.vendor_id' => 'nullable|integer|exists:vendors,id',
    ];

    protected function model(): string
    {
        return Brand::class;
    }

    protected function heading(): string
    {
        return __('hanbell.admin.brands');
    }

    protected function columns(): array
    {
        return ['name' => 'Name', 'country' => 'Country', 'is_featured' => 'Featured', 'is_active' => 'Active'];
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Name', 'type' => 'text'],
            'vendor_id' => ['label' => 'Owner brand', 'type' => 'select', 'options' => 'vendors'],
            'description' => ['label' => 'Description', 'type' => 'textarea'],
            'country' => ['label' => 'Country code', 'type' => 'text'],
            'logo_path' => ['label' => 'Logo path', 'type' => 'text'],
        ];
    }

    public function optionsFor(string $source): array
    {
        return match ($source) {
            'vendors' => Vendor::orderBy('name')->pluck('name', 'id')->all(),
            default => [],
        };
    }
}