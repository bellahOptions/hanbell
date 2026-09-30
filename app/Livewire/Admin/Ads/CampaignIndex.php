<?php

namespace App\Livewire\Admin\Ads;

use App\Enums\AdAudience;
use App\Enums\AdCampaignStatus;
use App\Enums\AdDevice;
use App\Enums\AdPlacementKey;
use App\Enums\AdPricingModel;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\AdCampaign;
use App\Models\AdCreative;
use App\Models\AdPlacement;
use App\Models\Advertiser;
use App\Models\Vendor;
use App\Services\Advertising\AdDestination;
use App\Services\Advertising\AdRanker;
use App\Services\Security\AuditLogger;
use App\Support\Money;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Campaign management.
 *
 * The destination URL is validated with AdDestination on save, so a campaign
 * that would be refused at click time cannot be created in the first place.
 * The same allow-list runs again on the click endpoint, so removing a host takes
 * effect immediately.
 */
#[Layout('layouts.admin')]
class CampaignIndex extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    /* Campaign fields -------------------------------------------------- */
    public string $name = '';

    public string $description = '';

    public string $statusValue = 'draft';

    public string $pricing_model = 'cpm';

    public ?int $advertiser_id = null;

    public ?int $vendor_id = null;

    /** Bid in major units for the form; converted to minor on save. */
    public float $bid = 0.0;

    public float $budget = 0.0;

    public ?string $starts_at = null;

    public ?string $ends_at = null;

    public string $audience = 'everyone';

    public string $device = 'all';

    public int $max_impressions_per_session = 3;

    public ?int $daily_impression_cap = null;

    public int $priority = 0;

    public bool $is_exclusive = false;

    /** @var array<int,string> */
    public array $placement_keys = [];

    /* Creative fields -------------------------------------------------- */
    public string $creative_name = '';

    public string $headline = '';

    public string $subheadline = '';

    public string $cta_label = 'Shop now';

    public string $destination_url = '';

    public string $image_url = '';

    public string $background_color = '#0b0b0a';

    public string $text_color = '#ffffff';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    /* ------------------------------------------------------------------ *
     * Form lifecycle
     * ------------------------------------------------------------------ */

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $campaign = AdCampaign::with('placements')->find($id);

        if (! $campaign) {
            return;
        }

        $this->editingId = $campaign->id;
        $this->name = $campaign->name;
        $this->description = (string) $campaign->description;
        $this->statusValue = $campaign->status->value;
        $this->pricing_model = $campaign->pricing_model->value;
        $this->advertiser_id = $campaign->advertiser_id;
        $this->vendor_id = $campaign->vendor_id;
        $this->bid = Money::toMajor((int) $campaign->bid_minor);
        $this->budget = Money::toMajor((int) $campaign->budget_minor);
        $this->starts_at = $campaign->starts_at?->format('Y-m-d\TH:i');
        $this->ends_at = $campaign->ends_at?->format('Y-m-d\TH:i');
        $this->audience = $campaign->audience->value;
        $this->device = $campaign->device->value;
        $this->max_impressions_per_session = (int) $campaign->max_impressions_per_session;
        $this->daily_impression_cap = $campaign->daily_impression_cap;
        $this->priority = (int) $campaign->priority;
        $this->is_exclusive = (bool) $campaign->is_exclusive;
        $this->placement_keys = $campaign->placements->pluck('key')->all();

        $creative = $campaign->creatives()->first();

        if ($creative) {
            $this->creative_name = $creative->name;
            $this->headline = (string) $creative->headline;
            $this->subheadline = (string) $creative->subheadline;
            $this->cta_label = (string) $creative->cta_label;
            $this->destination_url = (string) $creative->destination_url;
            $this->image_url = (string) ($creative->image_url ?: $creative->image_path);
            $this->background_color = (string) ($creative->background_color ?: '#0b0b0a');
            $this->text_color = (string) ($creative->text_color ?: '#ffffff');
        }

        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(AdDestination $destination, AuditLogger $audit): void
    {
        $this->validate([
            'name' => 'required|string|max:120',
            'pricing_model' => 'required|in:cpm,cpc,flat',
            'statusValue' => 'required|in:draft,active,paused,completed,archived',
            'bid' => 'required|numeric|min:0',
            'budget' => 'required|numeric|min:0',
            'audience' => 'required|in:everyone,guests,signed_in,new_customers,returning_customers',
            'device' => 'required|in:all,desktop,mobile,tablet',
            'headline' => 'required|string|max:120',
            'destination_url' => 'required|string|max:500',
            'placement_keys' => 'required|array|min:1',
        ], [
            'placement_keys.required' => 'Choose at least one placement for this campaign.',
        ]);

        // Refuse a destination that could not be followed at click time. Saving
        // it anyway would create a campaign that silently 404s on every click.
        if (! $destination->isSafe($this->destination_url)) {
            $this->addError('destination_url', $destination->rejectionReason($this->destination_url));

            return;
        }

        $attributes = [
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->statusValue,
            'pricing_model' => $this->pricing_model,
            'advertiser_id' => $this->advertiser_id ?: null,
            'vendor_id' => $this->vendor_id ?: null,
            'bid_minor' => Money::toMinor($this->bid),
            'budget_minor' => Money::toMinor($this->budget),
            'starts_at' => $this->starts_at ?: null,
            'ends_at' => $this->ends_at ?: null,
            'audience' => $this->audience,
            'device' => $this->device,
            'max_impressions_per_session' => $this->max_impressions_per_session,
            'daily_impression_cap' => $this->daily_impression_cap ?: null,
            'priority' => $this->priority,
            'is_exclusive' => $this->is_exclusive,
        ];

        if ($this->editingId) {
            $campaign = AdCampaign::findOrFail($this->editingId);
            $campaign->update($attributes);

            $audit->log('ad.campaign_updated', $campaign, 'Ad campaign updated', ['campaign' => $campaign->name]);
            $this->toastSuccess(__('hanbell.admin.saved'));
        } else {
            $campaign = AdCampaign::create($attributes);

            $audit->log('ad.campaign_created', $campaign, 'Ad campaign created', ['campaign' => $campaign->name]);
            $this->toastSuccess(__('hanbell.admin.created'));
        }

        // Sync the pivoted placements.
        $placementIds = AdPlacement::whereIn('key', $this->placement_keys)->pluck('id')->all();
        $campaign->placements()->sync($placementIds);

        // Upsert the first creative.
        $creative = $campaign->creatives()->first() ?? new AdCreative(['ad_campaign_id' => $campaign->id]);

        $creative->fill([
            'ad_campaign_id' => $campaign->id,
            'name' => $this->creative_name ?: $this->name,
            'headline' => $this->headline,
            'subheadline' => $this->subheadline,
            'cta_label' => $this->cta_label,
            'destination_url' => $this->destination_url,
            'image_url' => $this->image_url ?: null,
            'background_color' => $this->background_color,
            'text_color' => $this->text_color,
            'destination_type' => $destination->isInternal($this->destination_url) ? 'internal' : 'external',
        ])->save();

        $this->showForm = false;
        $this->resetForm();
    }

    public function setStatus(int $id, string $status, AuditLogger $audit): void
    {
        $campaign = AdCampaign::find($id);
        $next = AdCampaignStatus::tryFrom($status);

        if (! $campaign || ! $next) {
            return;
        }

        $campaign->forceFill(['status' => $next])->save();

        $audit->log('ad.campaign_status', $campaign, 'Campaign set to '.$next->label(), [
            'campaign' => $campaign->name,
            'status' => $next->value,
        ]);

        $this->toastSuccess($campaign->name.' is now '.$next->label().'.');
    }

    public function delete(int $id, AuditLogger $audit): void
    {
        $campaign = AdCampaign::find($id);

        if (! $campaign) {
            return;
        }

        // Soft delete: ad_events rows reference this campaign and must keep
        // resolving for historical reporting.
        $campaign->delete();

        $audit->log('ad.campaign_deleted', $campaign, 'Campaign deleted', ['campaign' => $campaign->name]);
        $this->toastSuccess(__('hanbell.admin.deleted'));
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->reset([
            'name', 'description', 'advertiser_id', 'vendor_id', 'starts_at', 'ends_at',
            'daily_impression_cap', 'placement_keys', 'creative_name', 'headline', 'subheadline',
            'destination_url', 'image_url',
        ]);

        $this->statusValue = 'draft';
        $this->pricing_model = 'cpm';
        $this->bid = 0.0;
        $this->budget = 0.0;
        $this->audience = 'everyone';
        $this->device = 'all';
        $this->max_impressions_per_session = 3;
        $this->priority = 0;
        $this->is_exclusive = false;
        $this->cta_label = 'Shop now';
        $this->background_color = '#0b0b0a';
        $this->text_color = '#ffffff';
        $this->resetErrorBag();
    }

    /* ------------------------------------------------------------------ */

    public function render(): View
    {
        return view('livewire.admin.ads.campaign-index', [
            'campaigns' => AdCampaign::query()
                ->with(['advertiser:id,name', 'vendor:id,name', 'placements:id,key'])
                ->withCount('creatives')
                ->when(filled($this->search), fn (Builder $q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->when(filled($this->status), fn (Builder $q) => $q->where('status', $this->status))
                ->latest()
                ->paginate(15),
            'placements' => AdPlacementKey::options(),
            'advertisers' => Advertiser::orderBy('name')->pluck('name', 'id')->all(),
            'vendors' => Vendor::approved()->orderBy('name')->pluck('name', 'id')->all(),
            'audiences' => AdAudience::options(),
            'devices' => AdDevice::options(),
            'pricingModels' => AdPricingModel::options(),
            'statuses' => collect(AdCampaignStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all(),
            'weights' => app(AdRanker::class)->weights(),
            'seo' => app(Seo::class)->title(__('hanbell.admin.campaigns'))->noindex(),
        ]);
    }
}