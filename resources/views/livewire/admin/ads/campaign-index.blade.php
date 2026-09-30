<div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.campaigns') }}</h1>
            <p class="mt-1 text-sm text-ink-500">
                {{ $campaigns->total() }} campaign(s) ·
                {{ count($placements) }} placements available
            </p>
        </div>

        <x-ui.button wire:click="create" variant="primary">
            <x-heroicon-o-plus class="size-4" />
            {{ __('hanbell.common.create') }}
        </x-ui.button>
    </div>

    {{-- ============================================================
         Campaign form
         ============================================================ --}}
    @if ($showForm)
        <x-admin.panel :title="$editingId ? __('hanbell.common.edit') : __('hanbell.common.create')"
                       description="Delivery is paced automatically across the flight; the ranking weights are in config/hanbell.php.">
            <form wire:submit="save" class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input wire:model="name" name="name" label="Campaign name" required class="sm:col-span-2" />

                    <x-ui.select wire:model="statusValue" name="statusValue" :label="__('hanbell.common.status')" :options="$statuses" />
                    <x-ui.select wire:model="pricing_model" name="pricing_model" label="Pricing model" :options="$pricingModels" />

                    <x-ui.select wire:model="advertiser_id" name="advertiser_id" label="Advertiser" :options="$advertisers" :placeholder="__('hanbell.common.none')" />
                    <x-ui.select wire:model="vendor_id" name="vendor_id" :label="__('hanbell.vendor.brand')" :options="$vendors" :placeholder="__('hanbell.common.none')" />

                    <x-ui.input wire:model="bid" name="bid" type="number" step="0.01" label="Bid (NGN)" hint="CPM: per 1,000 impressions. CPC: per click." />
                    <x-ui.input wire:model="budget" name="budget" type="number" step="0.01" label="Total budget (NGN)" required />

                    <x-ui.input wire:model="starts_at" name="starts_at" type="datetime-local" label="Starts at" />
                    <x-ui.input wire:model="ends_at" name="ends_at" type="datetime-local" label="Ends at" />

                    <x-ui.select wire:model="audience" name="audience" label="Audience" :options="$audiences" />
                    <x-ui.select wire:model="device" name="device" label="Device" :options="$devices" />

                    <x-ui.input wire:model="max_impressions_per_session" name="max_impressions_per_session" type="number" label="Max impressions per session" />
                    <x-ui.input wire:model="daily_impression_cap" name="daily_impression_cap" type="number" label="Daily impression cap" hint="Leave blank for no daily limit." />

                    <x-ui.input wire:model="priority" name="priority" type="number" label="Manual priority" hint="Nudges ranking without changing the bid." />

                    <label class="flex items-center gap-2.5 self-end text-sm text-ink-700">
                        <input type="checkbox" wire:model="is_exclusive" class="size-4 rounded border-ink-300 text-brand-600">
                        Exclusive — takes the whole slot when eligible
                    </label>
                </div>

                {{-- Placements --}}
                <div>
                    <p class="mb-2 text-sm font-bold text-ink-900">Placements</p>
                    <p class="mb-3 text-xs text-ink-500">Every slot is a fixed, named position with a real call site on the site.</p>

                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($placements as $key => $label)
                            <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-ink-200 p-3 text-sm transition hover:border-brand-600/40">
                                <input type="checkbox" wire:model="placement_keys" value="{{ $key }}" class="mt-0.5 size-4 rounded border-ink-300 text-brand-600">
                                <span class="min-w-0 text-ink-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    @error('placement_keys')
                        <p class="mt-2 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Creative --}}
                <div class="rounded-xl border border-ink-200 p-4">
                    <p class="mb-3 text-sm font-bold text-ink-900">Creative</p>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.input wire:model="creative_name" name="creative_name" label="Creative name" class="sm:col-span-2" />
                        <x-ui.input wire:model="headline" name="headline" label="Headline" required />
                        <x-ui.input wire:model="subheadline" name="subheadline" label="Sub-headline" />
                        <x-ui.input wire:model="cta_label" name="cta_label" label="Button label" />
                        <x-ui.input wire:model="image_url" name="image_url" label="Image URL" />

                        <x-ui.input
                            wire:model="destination_url"
                            name="destination_url"
                            label="Destination URL"
                            hint="An internal path such as /shop, or a full https:// address on an allowed host."
                            required
                            class="sm:col-span-2"
                        />

                        <div>
                            <label for="bg" class="mb-1.5 block text-sm font-medium text-ink-800">Background colour</label>
                            <input id="bg" type="color" wire:model="background_color" class="h-11 w-full rounded-lg border border-ink-300">
                        </div>

                        <div>
                            <label for="fg" class="mb-1.5 block text-sm font-medium text-ink-800">Text colour</label>
                            <input id="fg" type="color" wire:model="text_color" class="h-11 w-full rounded-lg border border-ink-300">
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <x-ui.button type="submit" variant="primary" loading="save">
                        <span wire:loading.remove wire:target="save">{{ __('hanbell.common.save') }}</span>
                        <span wire:loading wire:target="save">{{ __('hanbell.common.loading') }}</span>
                    </x-ui.button>

                    <x-ui.button type="button" wire:click="cancel" variant="ghost">{{ __('hanbell.common.cancel') }}</x-ui.button>
                </div>
            </form>
        </x-admin.panel>
    @endif

    {{-- ============================================================
         Campaign list
         ============================================================ --}}
    <x-admin.panel padding="none">
        <div class="border-b border-ink-100 p-4">
            <x-admin.toolbar search="search">
                <select wire:model.live="status" class="h-10 rounded-lg border border-ink-300 bg-white px-3 text-sm">
                    <option value="">{{ __('hanbell.common.all') }}</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </x-admin.toolbar>
        </div>

        @if ($campaigns->isEmpty())
            <x-ui.empty-state icon="megaphone" :title="__('hanbell.common.no_items')" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-ink-200 bg-ink-50/60">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-500">
                            <th scope="col" class="px-4 py-2.5">Campaign</th>
                            <th scope="col" class="px-4 py-2.5">Delivery</th>
                            <th scope="col" class="px-4 py-2.5 text-right">Impressions</th>
                            <th scope="col" class="px-4 py-2.5 text-right">Clicks</th>
                            <th scope="col" class="px-4 py-2.5 text-right">CTR</th>
                            <th scope="col" class="px-4 py-2.5">Budget</th>
                            <th scope="col" class="px-4 py-2.5 text-right">{{ __('hanbell.common.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100">
                        @foreach ($campaigns as $campaign)
                            <tr class="transition hover:bg-ink-50/60">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-ink-900">{{ $campaign->name }}</p>
                                    <p class="mt-0.5 text-xs text-ink-500">
                                        {{ $campaign->advertiser?->name ?? $campaign->vendor?->name ?? 'House' }}
                                        · {{ $campaign->creatives_count }} creative(s)
                                    </p>
                                </td>

                                <td class="px-4 py-3">
                                    <x-ui.badge :class="$campaign->status->badgeClasses()" size="sm">
                                        {{ $campaign->status->label() }}
                                    </x-ui.badge>

                                    @php $progress = $campaign->flightProgress(); @endphp
                                    @if ($progress !== null)
                                        <p class="mt-1 text-[11px] text-ink-400">
                                            {{ round($progress * 100) }}% of flight elapsed
                                        </p>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-right tabular-nums text-ink-700">
                                    {{ number_format($campaign->impressions_count) }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-ink-700">
                                    {{ number_format($campaign->clicks_count) }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-ink-900">
                                    {{ $campaign->clickThroughRate() }}%
                                </td>

                                {{-- Budget consumption bar, so pacing is visible at a glance --}}
                                <td class="px-4 py-3">
                                    <p class="text-xs font-semibold tabular-nums text-ink-800">
                                        {{ \App\Support\Money::compact($campaign->spend_minor) }}
                                        / {{ \App\Support\Money::compact($campaign->budget_minor) }}
                                    </p>
                                    <div class="mt-1.5 h-1.5 w-24 overflow-hidden rounded-full bg-ink-100">
                                        <div
                                            @class([
                                                'h-full rounded-full transition-all',
                                                'bg-danger-500' => $campaign->budgetConsumedPercent() >= 90,
                                                'bg-warning-500' => $campaign->budgetConsumedPercent() >= 70 && $campaign->budgetConsumedPercent() < 90,
                                                'bg-brand-600' => $campaign->budgetConsumedPercent() < 70,
                                            ])
                                            style="width: {{ $campaign->budgetConsumedPercent() }}%"
                                        ></div>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($campaign->status->value === 'active')
                                            <button type="button" wire:click="setStatus({{ $campaign->id }}, 'paused')"
                                                class="rounded-md p-1.5 text-ink-400 transition hover:bg-ink-100" title="Pause">
                                                <x-heroicon-o-pause class="size-4" />
                                            </button>
                                        @else
                                            <button type="button" wire:click="setStatus({{ $campaign->id }}, 'active')"
                                                class="rounded-md p-1.5 text-brand-600 transition hover:bg-brand-50" title="Activate">
                                                <x-heroicon-o-play class="size-4" />
                                            </button>
                                        @endif

                                        <button type="button" wire:click="edit({{ $campaign->id }})"
                                            class="rounded-md p-1.5 text-ink-400 transition hover:bg-ink-100">
                                            <x-heroicon-o-pencil-square class="size-4" />
                                        </button>

                                        <button type="button" wire:click="delete({{ $campaign->id }})"
                                            wire:confirm="{{ __('hanbell.admin.confirm_delete') }}"
                                            class="rounded-md p-1.5 text-ink-400 transition hover:bg-danger-50 hover:text-danger-600">
                                            <x-heroicon-o-trash class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 p-4">{{ $campaigns->links() }}</div>
        @endif
    </x-admin.panel>

    {{-- Ranking weights, surfaced so the algorithm is inspectable --}}
    <x-admin.panel title="Ranking weights" description="How the ad server values an impression. Edit config/hanbell.php → ads.ranking to change these.">
        <dl class="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                'Bid' => 'Base value: the campaign bid, converted to a per-impression figure.',
                'Pacing' => 'Clamped ×'.($weights['pacing']['min'] ?? '?').'–×'.($weights['pacing']['max'] ?? '?').' — a campaign behind schedule is worth more.',
                'Relevance' => 'Up to ±'.(($weights['relevance']['weight'] ?? 0) * 100).'% for how well targeting matches the page.',
                'Quality' => 'Clamped ×'.($weights['quality']['min'] ?? '?').'–×'.($weights['quality']['max'] ?? '?').' on historical CTR vs a '.((($weights['quality']['baseline_ctr'] ?? 0.02)) * 100).'% baseline.',
                'Fatigue' => 'Halves for each additional placement of the same campaign on one page.',
                'Priority' => 'Manual nudge, ±'.(($weights['priority']['per_point'] ?? 0.05) * 100).'% per point.',
            ] as $label => $explanation)
                <div class="rounded-lg border border-ink-200 p-3">
                    <dt class="text-xs font-bold uppercase tracking-wider text-ink-500">{{ $label }}</dt>
                    <dd class="mt-1 text-xs leading-relaxed text-ink-600">{{ $explanation }}</dd>
                </div>
            @endforeach
        </dl>
    </x-admin.panel>
</div>
