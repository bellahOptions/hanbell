<?php

namespace App\Livewire\Admin\Ads;

use App\Enums\AdPlacementKey;
use App\Models\AdCampaign;
use App\Models\AdPlacement;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The placement registry.
 *
 * Slots are a closed set defined by AdPlacementKey; this screen shows every one
 * of them with what is currently booked, so an operator can see gaps at a
 * glance. Rows come from the enum, not from the table, so a slot that has never
 * been seeded still appears.
 */
#[Layout('layouts.admin')]
class PlacementIndex extends Component
{
    public function render(): View
    {
        $placements = collect(AdPlacementKey::cases())->map(function (AdPlacementKey $key) {
            $model = AdPlacement::where('key', $key->value)->first();

            return [
                'key' => $key->value,
                'label' => $key->label(),
                'description' => $key->description(),
                'size' => $key->recommendedSize(),
                'max' => $key->maxCreatives(),
                'active' => $model?->is_active ?? true,
                'campaigns' => $model
                    ? AdCampaign::whereHas('placements', fn ($q) => $q->where('ad_placements.id', $model->id))
                        ->where('status', 'active')
                        ->count()
                    : 0,
            ];
        });

        return view('livewire.admin.ads.placement-index', [
            'placements' => $placements,
            'seo' => app(Seo::class)->title(__('hanbell.admin.placements'))->noindex(),
        ]);
    }
}