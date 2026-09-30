<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.reviews') }}</h1>
        <p class="mt-1 text-sm text-ink-500">
            {{ $pendingCount }} awaiting moderation.
            Approving or rejecting a review recomputes the product's rating aggregate.
        </p>
    </div>

    <div class="flex flex-wrap gap-1.5">
        @foreach (['pending' => __('hanbell.admin.pending_review'), 'approved' => __('hanbell.order.statuses.paid'), 'all' => __('hanbell.common.all')] as $value => $label)
            <button
                type="button"
                wire:click="$set('filter', '{{ $value }}')"
                @class([
                    'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                    'bg-ink-950 text-white' => $filter === $value,
                    'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100' => $filter !== $value,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <x-admin.panel padding="none">
        @if ($reviews->isEmpty())
            <x-ui.empty-state icon="star" :title="__('hanbell.common.no_items')" />
        @else
            <ul class="divide-y divide-ink-100">
                @foreach ($reviews as $review)
                    <li class="p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-3">
                                    <x-ui.stars :rating="$review->rating" :show-count="false" size="sm" />

                                    @if ($review->is_approved)
                                        <x-ui.badge variant="brand" size="xs">{{ __('hanbell.order.statuses.paid') }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="warning" size="xs">{{ __('hanbell.admin.pending_review') }}</x-ui.badge>
                                    @endif

                                    @if ($review->is_verified_purchase)
                                        <x-ui.badge variant="info" size="xs">Verified purchase</x-ui.badge>
                                    @endif
                                </div>

                                @if ($review->title)
                                    <p class="mt-2 text-sm font-semibold text-ink-900">{{ $review->title }}</p>
                                @endif

                                @if ($review->body)
                                    <p class="mt-1 text-sm leading-relaxed text-ink-600">{{ $review->body }}</p>
                                @endif

                                <p class="mt-2 text-xs text-ink-500">
                                    {{ $review->user?->name ?? 'Unknown' }}
                                    on <a href="{{ $review->product?->publicUrl() }}" target="_blank" rel="noopener" class="text-brand-700 underline underline-offset-2">{{ $review->product?->name ?? 'a deleted product' }}</a>
                                    · {{ $review->created_at->format('j M Y') }}
                                </p>

                                @if ($review->moderation_note)
                                    <p class="mt-2 rounded-lg bg-danger-50 p-2.5 text-xs text-danger-700">
                                        {{ $review->moderation_note }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                @unless ($review->is_approved)
                                    <x-ui.button wire:click="approve({{ $review->id }})" variant="primary" size="sm">
                                        {{ __('hanbell.common.confirm') }}
                                    </x-ui.button>
                                @endunless

                                <x-ui.button wire:click="startReject({{ $review->id }})" variant="outline" size="sm">
                                    {{ __('hanbell.common.delete') }}
                                </x-ui.button>
                            </div>
                        </div>

                        @if ($rejectingId === $review->id)
                            <div class="mt-3 space-y-2 rounded-lg border border-ink-200 p-3">
                                <label for="note-{{ $review->id }}" class="sr-only">Reason</label>
                                <input
                                    id="note-{{ $review->id }}"
                                    type="text"
                                    wire:model="note"
                                    placeholder="Why is this review being removed?"
                                    class="h-9 w-full rounded-md border border-ink-300 px-2.5 text-sm focus:border-danger-500 focus:outline-none"
                                >

                                <div class="flex gap-2">
                                    <button type="button" wire:click="reject"
                                        class="rounded-md bg-danger-600 px-3 py-1.5 text-xs font-semibold text-white">
                                        {{ __('hanbell.common.confirm') }}
                                    </button>
                                    <button type="button" wire:click="$set('rejectingId', null)" class="rounded-md px-3 py-1.5 text-xs text-ink-500">
                                        {{ __('hanbell.common.cancel') }}
                                    </button>
                                </div>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>

            <div class="border-t border-ink-100 p-4">{{ $reviews->links() }}</div>
        @endif
    </x-admin.panel>
</div>
