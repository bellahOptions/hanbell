<?php

namespace App\Livewire\Admin\Catalogue;

use App\Livewire\Admin\ResourceIndex;
use App\Models\Attribute;

class AttributeIndex extends ResourceIndex
{
    protected array $validationRules = [
        'form.name' => 'required|string|max:80',
        'form.type' => 'nullable|string|in:select,multiselect,text,boolean',
    ];

    protected function model(): string
    {
        return Attribute::class;
    }

    protected function heading(): string
    {
        return __('hanbell.admin.attributes');
    }

    protected function columns(): array
    {
        return ['name' => 'Name', 'type' => 'Type', 'is_filterable' => 'Filterable', 'is_variant_axis' => 'Variant axis'];
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Name', 'type' => 'text'],
            'type' => ['label' => 'Type', 'type' => 'select', 'options' => ['select' => 'Select', 'multiselect' => 'Multi-select', 'text' => 'Text', 'boolean' => 'Yes / No']],
        ];
    }
}