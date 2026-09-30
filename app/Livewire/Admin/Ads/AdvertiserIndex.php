<?php

namespace App\Livewire\Admin\Ads;

use App\Livewire\Admin\ResourceIndex;
use App\Models\Advertiser;
use App\Models\Vendor;

class AdvertiserIndex extends ResourceIndex
{
    protected array $validationRules = [
        'form.name' => 'required|string|max:120',
        'form.vendor_id' => 'nullable|integer|exists:vendors,id',
        'form.contact_email' => 'nullable|email|max:255',
    ];

    protected function model(): string
    {
        return Advertiser::class;
    }

    protected function heading(): string
    {
        return __('hanbell.admin.advertisers');
    }

    protected function columns(): array
    {
        return ['name' => 'Advertiser', 'contact_email' => 'Contact', 'is_active' => 'Active', 'created_at' => 'Added'];
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Advertiser name', 'type' => 'text'],
            'vendor_id' => ['label' => 'Linked brand', 'type' => 'select', 'options' => 'vendors'],
            'contact_name' => ['label' => 'Contact name', 'type' => 'text'],
            'contact_email' => ['label' => 'Contact email', 'type' => 'email'],
            'contact_phone' => ['label' => 'Contact phone', 'type' => 'text'],
            'billing_reference' => ['label' => 'Billing reference', 'type' => 'text'],
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