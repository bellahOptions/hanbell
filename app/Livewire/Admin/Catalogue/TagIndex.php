<?php

namespace App\Livewire\Admin\Catalogue;

use App\Livewire\Admin\ResourceIndex;
use App\Models\Tag;

class TagIndex extends ResourceIndex
{
    protected array $validationRules = [
        'form.name' => 'required|string|max:80',
        'form.type' => 'nullable|string|in:general,style,occasion,fabric',
    ];

    protected function model(): string
    {
        return Tag::class;
    }

    protected function heading(): string
    {
        return __('hanbell.admin.tags');
    }

    protected function columns(): array
    {
        return ['name' => 'Name', 'type' => 'Type', 'use_count' => 'Used', 'created_at' => 'Created'];
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Name', 'type' => 'text'],
            'type' => ['label' => 'Type', 'type' => 'select', 'options' => ['general' => 'General', 'style' => 'Style', 'occasion' => 'Occasion', 'fabric' => 'Fabric']],
        ];
    }

    protected function sortable(): array
    {
        return ['name', 'use_count', 'created_at'];
    }
}