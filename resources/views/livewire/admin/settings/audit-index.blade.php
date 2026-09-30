<div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.audit_log') }}</h1>
            <p class="mt-1 text-sm text-ink-500">
                Append-only. Credentials are redacted at write time, so nothing sensitive can appear here.
            </p>
        </div>
    </div>

    <x-admin.panel padding="none">
        <div class="border-b border-ink-100 p-4">
            <x-admin.toolbar search="search" searchPlaceholder="Description, event or IP…">
                <select wire:model.live="event" class="h-10 max-w-xs rounded-lg border border-ink-300 bg-white px-3 text-sm">
                    <option value="">{{ __('hanbell.common.all') }}</option>
                    @foreach ($events as $eventName)
                        <option value="{{ $eventName }}">{{ $eventName }}</option>
                    @endforeach
                </select>
            </x-admin.toolbar>
        </div>

        @if ($logs->isEmpty())
            <x-ui.empty-state icon="clipboard-document-list" :title="__('hanbell.common.no_items')" />
        @else
            <ul class="divide-y divide-ink-100">
                @foreach ($logs as $log)
                    <li class="flex flex-wrap items-start gap-3 px-5 py-3.5">
                        {{-- Tone dot, keyed off the event family --}}
                        <span @class([
                            'mt-1.5 size-2 shrink-0 rounded-full',
                            'bg-brand-500' => $log->tone() === 'brand',
                            'bg-info-500' => $log->tone() === 'info',
                            'bg-warning-500' => $log->tone() === 'warning',
                            'bg-danger-500' => $log->tone() === 'danger',
                            'bg-ink-300' => $log->tone() === 'neutral',
                        ]) aria-hidden="true"></span>

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-ink-900">{{ $log->label() }}</p>

                            <p class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-ink-500">
                                <code class="rounded bg-ink-100 px-1.5 py-0.5 font-mono">{{ $log->event }}</code>

                                @if ($log->user)
                                    <span>{{ $log->user->name }}</span>
                                @else
                                    <span class="text-ink-400">system</span>
                                @endif

                                <span>{{ $log->ip_address ?: '—' }}</span>
                                <span>{{ $log->created_at?->format('j M Y H:i') }}</span>
                            </p>

                            @if ($log->properties)
                                <details class="mt-1.5">
                                    <summary class="cursor-pointer text-[11px] font-medium text-brand-700">Details</summary>
                                    <pre class="mt-1.5 overflow-x-auto rounded-lg bg-ink-50 p-2.5 font-mono text-[10px] leading-relaxed text-ink-600">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </details>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="border-t border-ink-100 p-4">{{ $logs->links() }}</div>
        @endif
    </x-admin.panel>
</div>
