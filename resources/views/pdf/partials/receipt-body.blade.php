@php
    $payment = $payment ?? $order->payments->firstWhere('status', \App\Enums\PaymentStatus::Succeeded) ?? $order->payments->first();
    $refundedMinor = (int) ($payment?->refunded_minor ?? 0);
    $netMinor = (int) ($payment?->amount_minor ?? $order->total_minor) - $refundedMinor;
@endphp

<table class="panels">
    <tr>
        <td>
            <p class="panel-label">Received from</p>
            <p class="party-name">{{ $recipient->name }}</p>

            @foreach ($recipient->addressLines as $line)
                <p class="party-line">{{ $line }}</p>
            @endforeach

            @if ($recipient->email)
                <p class="party-meta">{{ $recipient->email }}</p>
            @endif
        </td>
        <td>
            <table class="facts">
                <tr>
                    <td class="k">Receipt number</td>
                    <td class="v">{{ $receiptNumber }}</td>
                </tr>
                <tr>
                    <td class="k">Order reference</td>
                    <td class="v">{{ $order->number }}</td>
                </tr>
                <tr>
                    <td class="k">Date received</td>
                    <td class="v">{{ ($payment?->paid_at ?? $order->paid_at ?? $issueDate)->format('j M Y') }}</td>
                </tr>
                <tr>
                    <td class="k">Currency</td>
                    <td class="v">{{ $order->currency }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="block avoid-break" style="margin-top: 0;">
    <h3>Amount received</h3>
    <table class="totals" style="width: 100%;">
        <tr class="grand">
            <td class="k">Total received</td>
            <td class="v">{{ \App\Support\Money::format((int) ($payment?->amount_minor ?? $order->total_minor), $order->currency) }}</td>
        </tr>

        @if ($refundedMinor > 0)
            <tr>
                <td class="k">Refunded</td>
                <td class="v discount-v">&minus;{{ \App\Support\Money::format($refundedMinor, $order->currency) }}</td>
            </tr>
            <tr class="balance">
                <td class="k">Net retained</td>
                <td class="v">{{ \App\Support\Money::format($netMinor, $order->currency) }}</td>
            </tr>
        @endif
    </table>
</div>

@if ($payment)
    <div class="block avoid-break">
        <h3>Payment details</h3>
        <table class="facts">
            <tr>
                <td class="k">Method</td>
                <td class="v">{{ $payment->providerLabel() }}</td>
            </tr>
            <tr>
                <td class="k">Reference</td>
                <td class="v">{{ $payment->reference }}</td>
            </tr>
            @if ($payment->provider_reference)
                <tr>
                    <td class="k">Provider reference</td>
                    <td class="v">{{ $payment->provider_reference }}</td>
                </tr>
            @endif
            @if ($payment->channel)
                <tr>
                    <td class="k">Channel</td>
                    <td class="v">{{ ucfirst($payment->channel) }}</td>
                </tr>
            @endif
            <tr>
                <td class="k">Status</td>
                <td class="v">{{ $payment->status->label() }}</td>
            </tr>
        </table>
    </div>
@endif

<div class="block avoid-break">
    <h3>For</h3>
    {{--
        A receipt is proof of payment, not an itemised bill.

        Listing every line again would duplicate the invoice and push the
        document onto a second A5 sheet. What the customer needs here is the
        order total and the payment reference that proves it was settled; a
        per-item breakdown adds bulk without adding proof.
    --}}
    <p class="small" style="margin: 0 0 1.5mm 0;">
        {{ trans_choice('hanbell.shop.results_count', $lines ? count($lines) : 0, ['count' => count($lines)]) }}
        on order {{ $order->number }}@if ($order->items->isNotEmpty()), including
        @foreach ($order->items->take(3) as $item)
            {{ $item->product_name }}@if (! $loop->last), @endif
        @endforeach
        @if ($order->items->count() > 3)
            and {{ $order->items->count() - 3 }} more
        @endif
        @endif.
    </p>

    <table class="totals" style="width: 100%; margin-top: 1mm;">
        <tr>
            <td class="k">Goods, delivery and any applicable VAT</td>
            <td class="v">{{ $totals->formattedTotal() }}</td>
        </tr>
    </table>
</div>

<p class="small" style="margin-top: 3mm;">
    This is a receipt, not a tax invoice. {{ $brandName }} is a product of {{ $operator['name'] }}@if ($operator['registration_number']), RC {{ $operator['registration_number'] }}@endif.
    Keep it for your records and for any warranty or returns claim relating to
    order {{ $order->number }}.
</p>
