{{--
    Document downloads for an order.

    Only documents that genuinely exist for the order's current state are
    offered: an invoice appears once the order is paid (an invoice is a demand
    for payment, so issuing one earlier is wrong), a credit note only once
    something has been refunded.

    Downloads are plain links rather than Livewire actions: the response is a
    file, and routing bytes through the Livewire channel would be wasteful.
--}}
@php
    $orderToken = $order->urlToken();
    $isPaid = $order->isPaid();
    $hasRefund = (int) $order->payments->sum('refunded_minor') > 0
        || $order->status === \App\Enums\OrderStatus::Refunded;
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }}>
    @if ($isPaid)
        <x-ui.button
            :href="route('documents.invoice', ['order' => $orderToken])"
            variant="outline"
            size="sm"
            target="_blank"
            rel="noopener"
        >
            <x-heroicon-o-document-text class="size-4" />
            Invoice
        </x-ui.button>

        <x-ui.button
            :href="route('documents.receipt', ['order' => $orderToken])"
            variant="outline"
            size="sm"
            target="_blank"
            rel="noopener"
        >
            <x-heroicon-o-receipt-percent class="size-4" />
            Receipt
        </x-ui.button>
    @endif

    <x-ui.button
        :href="route('documents.packing-slip', ['order' => $orderToken])"
        variant="outline"
        size="sm"
        target="_blank"
        rel="noopener"
    >
        <x-heroicon-o-clipboard-document-list class="size-4" />
        Packing slip
    </x-ui.button>

    @if ($hasRefund)
        <x-ui.button
            :href="route('documents.credit-note', ['order' => $orderToken])"
            variant="outline"
            size="sm"
            target="_blank"
            rel="noopener"
        >
            <x-heroicon-o-arrow-uturn-left class="size-4" />
            Credit note
        </x-ui.button>
    @endif
</div>
