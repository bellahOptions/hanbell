@props([])

{{--
    Branded page-transition loader.

    Note the event target: Livewire dispatches `livewire:navigating` and
    `livewire:navigated` on `document`, not `window`. Listening on `window`
    silently never fires — an easy and invisible mistake.

    It only appears if a navigation takes longer than 150ms, and once shown
    stays for at least 250ms. Without those two timers a fast transition flashes
    the loader for a few milliseconds, which reads as a glitch rather than as
    feedback.
--}}
<div
    id="hb-page-loader"
    class="pointer-events-none fixed inset-0 z-[9998] flex items-center justify-center bg-white/70 opacity-0 backdrop-blur-sm transition-opacity duration-200"
    aria-hidden="true"
>
    <div class="flex flex-col items-center gap-3">
        <div class="relative size-14">
            <span class="absolute inset-0 animate-ping rounded-2xl bg-brand-600/20"></span>
            <span class="relative flex size-14 items-center justify-center rounded-2xl bg-ink-950">
                <img
                    src="{{ asset('images/logo-wt.svg') }}"
                    alt=""
                    class="h-[120%] w-auto max-w-none -translate-x-[7%] animate-pulse"
                >
            </span>
        </div>

        <span class="text-xs font-semibold uppercase tracking-widest text-ink-500">
            {{ __('hanbell.common.loading') }}
        </span>
    </div>
</div>

<script>
    (() => {
        const loader = document.getElementById('hb-page-loader');
        if (!loader) return;

        const DELAY_BEFORE_SHOW = 150;
        const MINIMUM_VISIBLE = 250;

        let showTimer = null;
        let hideTimer = null;
        let shownAt = null;

        const show = () => {
            showTimer = setTimeout(() => {
                shownAt = Date.now();
                loader.classList.remove('opacity-0');
            }, DELAY_BEFORE_SHOW);
        };

        const hide = () => {
            clearTimeout(showTimer);

            if (shownAt === null) return;

            const visibleFor = Date.now() - shownAt;
            const remaining = Math.max(0, MINIMUM_VISIBLE - visibleFor);

            hideTimer = setTimeout(() => {
                loader.classList.add('opacity-0');
                shownAt = null;
            }, remaining);
        };

        // Dispatched on `document`, never `window`.
        document.addEventListener('livewire:navigating', show);
        document.addEventListener('livewire:navigated', hide);
    })();
</script>
