<?php

namespace App\Livewire\Admin\Catalogue;

use App\Livewire\Admin\ResourceIndex;
use App\Models\Category;
use App\Models\Department;

class CategoryIndex extends ResourceIndex
{
    protected array $validationRules = [
        'form.name' => 'required|string|max:120',
        'form.department_id' => 'nullable|integer|exists:departments,id',
        'form.commission_percent' => 'nullable|numeric|min:0|max:100',
        'form.position' => 'nullable|integer|min:0',
    ];

    protected function model(): string
    {
        return Category::class;
    }

    protected function heading(): string
    {
        return __('hanbell.admin.categories');
    }

    protected function columns(): array
    {
        return ['name' => 'Name', 'department' => 'Department', 'products_count' => 'Products', 'is_active' => 'Active'];
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Name', 'type' => 'text'],
            'department_id' => ['label' => 'Department', 'type' => 'select', 'options' => 'departments'],
            'description' => ['label' => 'Description', 'type' => 'textarea'],
            'image_path' => ['label' => 'Image path', 'type' => 'text'],
            'icon' => ['label' => 'Heroicon name', 'type' => 'text'],
            'commission_percent' => ['label' => 'Commission % override', 'type' => 'number'],
            'position' => ['label' => 'Sort position', 'type' => 'number'],
            'meta_title' => ['label' => 'SEO title', 'type' => 'text'],
            'meta_description' => ['label' => 'SEO description', 'type' => 'textarea'],
        ];
    }

    protected function searchable(): array
    {
        return ['name', 'slug'];
    }

    /** Options for select fields, resolved once for the form. */
    public function optionsFor(string $source): array
    {
        return match ($source) {
            'departments' => Department::orderBy('name')->pluck('name', 'id')->all(),
            default => [],
        };
    }
}