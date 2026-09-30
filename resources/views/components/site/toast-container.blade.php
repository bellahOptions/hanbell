@props([])

{{-- Toast host. The visible toasts are created by resources/js/app.js in
     response to a `toast` browser event, so Livewire components only ever need
     to dispatch an event — they never render markup for feedback. --}}
<div
    id="hb-toasts"
    class="pointer-events-none fixed inset-x-0 bottom-0 z-[9999] flex flex-col items-center gap-2 p-4 sm:inset-x-auto sm:bottom-auto sm:right-0 sm:top-0 sm:items-end"
    role="region"
    aria-label="{{ __('hanbell.toast.info') }}"
    aria-live="polite"
></div>

{{-- A full-page flash message rendered as a toast on load, for a redirect that
     carries feedback (e.g. returning from a payment gateway). --}}
@if (session('toast'))
    @php $flash = session('toast'); @endphp
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.hbToast?.(@json($flash));
        });
    </script>
@endif
