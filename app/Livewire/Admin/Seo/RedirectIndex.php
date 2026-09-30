<?php

namespace App\Livewire\Admin\Seo;

use App\Livewire\Admin\ResourceIndex;
use App\Models\Redirect;

class RedirectIndex extends ResourceIndex
{
    protected array $validationRules = [
        'form.from_path' => 'required|string|max:255',
        'form.to_path' => 'required|string|max:255',
        'form.status_code' => 'required|integer|in:301,302,307,308',
    ];

    protected function model(): string
    {
        return Redirect::class;
    }

    protected function heading(): string
    {
        return __('hanbell.admin.redirects');
    }

    protected function columns(): array
    {
        return ['from_path' => 'From', 'to_path' => 'To', 'status_code' => 'Code', 'hits' => 'Hits', 'is_active' => 'Active'];
    }

    protected function fields(): array
    {
        return [
            'from_path' => ['label' => 'From path', 'type' => 'text'],
            'to_path' => ['label' => 'To path', 'type' => 'text'],
            'status_code' => ['label' => 'Status code', 'type' => 'select', 'options' => [
                301 => '301 — Moved permanently',
                302 => '302 — Found (temporary)',
                307 => '307 — Temporary redirect',
                308 => '308 — Permanent redirect',
            ]],
        ];
    }

    protected function searchable(): array
    {
        return ['from_path', 'to_path'];
    }
}