<?php

namespace App\Livewire\Admin\Catalogue;

use App\Livewire\Admin\ResourceIndex;
use App\Models\Department;

class DepartmentIndex extends ResourceIndex
{
    protected array $validationRules = [
        'form.name' => 'required|string|max:120',
        'form.gender' => 'nullable|string|in:women,men,unisex,kids',
        'form.position' => 'nullable|integer|min:0',
    ];

    protected function model(): string
    {
        return Department::class;
    }

    protected function heading(): string
    {
        return __('hanbell.admin.departments');
    }

    protected function columns(): array
    {
        return ['name' => 'Name', 'gender' => 'Shop for', 'is_active' => 'Active'];
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Name', 'type' => 'text'],
            'gender' => ['label' => 'Shop for', 'type' => 'select', 'options' => ['women' => 'Women', 'men' => 'Men', 'unisex' => 'Unisex', 'kids' => 'Kids']],
            'description' => ['label' => 'Description', 'type' => 'textarea'],
            'image_path' => ['label' => 'Image path', 'type' => 'text'],
            'position' => ['label' => 'Sort position', 'type' => 'number'],
        ];
    }
}