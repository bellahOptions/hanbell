@props(['count' => 8, 'columns' => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4'])

{{-- Product-grid skeleton. Shown while a Livewire list resolves so the layout
     does not jump between the placeholder and the real grid. --}}
<div {{ $attributes->merge(['class' => 'grid gap-4 sm:gap-5 '.$columns]) }}>
    @for ($i = 0; $i < $count; $i++)
        <div class="overflow-hidden rounded-xl border border-ink-200 bg-white">
            <div class="hb-skeleton aspect-[3/4] w-full"></div>

            <div class="space-y-2.5 p-3.5">
                <div class="hb-skeleton h-3 w-1/3 rounded-full"></div>
                <div class="hb-skeleton h-4 w-4/5 rounded-full"></div>
                <div class="hb-skeleton h-5 w-1/2 rounded-full"></div>
            </div>
        </div>
    @endfor
</div>
