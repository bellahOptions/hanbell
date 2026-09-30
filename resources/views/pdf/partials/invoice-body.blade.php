@php
    $brands = $order->items->pluck('vendor_name')->unique()->filter();
    $showVendorColumn = $brands->count() > 1;
    $payment = $order->payments->firstWhere('status', \App\Enums\PaymentStatus::Succeeded) ?? $order->payments->first();
@endphp

<table class="panels">
    <tr>
        <td>
            <p class="panel-label">Billed to</p>
            <p class="party-name">{{ $recipient->name }}</p>

            @foreach ($recipient->addressLines as $line)
                <p class="party-line">{{ $line }}</p>
            @endforeach

            @if ($recipient->email)
                <p class="party-meta">{{ $recipient->email }}</p>
            @endif

            @if ($order->phone)
                <p class="party-meta">{{ $order->phone }}</p>
            @endif
        </td>
        <td>
            <table class="facts">
                <tr>
                    <td class="k">Invoice number</td>
                    <td class="v">{{ $invoiceNumber }}</td>
                </tr>
                <tr>
                    <td class="k">Order reference</td>
                    <td class="v">{{ $order->number }}</td>
                </tr>
                <tr>
                    <td class="k">Issue date</td>
                    <td class="v">{{ $issueDate->format('j M Y') }}</td>
                </tr>
                @if ($dueDate)
                    <tr>
                        <td class="k">Payment due</td>
                        <td class="v">{{ $dueDate->format('j M Y') }}</td>
                    </tr>
                @endif
                @if ($paidAt)
                    <tr>
                        <td class="k">Paid on</td>
                        <td class="v">{{ $paidAt->format('j M Y') }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="k">Currency</td>
                    <td class="v">{{ $order->currency }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@include('pdf.partials.lines', [
    'lines' => $lines,
])

@if ($order->isPaid() && ! ($totals->balanceMinor > 0))
    {{-- A paid order gets the stamp and the acknowledgement, in the flow above
         the totals rather than beside them. --}}
    <div class="avoid-break" style="margin-top: 4mm;">
        <span class="paid-stamp">Paid</span>
        <p class="note">
            Settled in full on {{ $order->paid_at?->format('j F Y') }}
            @if ($payment)
                via {{ $payment->providerLabel() }}
            @endif.
            Thank you.
        </p>
    </div>
@elseif ($totals->balanceMinor > 0)
    <table class="panels avoid-break" style="margin-top: 4mm; margin-bottom: 0;">
        <tr>
            <td>
                <span class="badge due">Payment due</span>
                <p class="note" style="margin-top: 1.5mm;">
                    {{ \App\Support\Money::format($totals->balanceMinor, $order->currency) }}
                    outstanding. Quote invoice {{ $invoiceNumber }} with your payment so
                    it is matched to this order.
                </p>
            </td>
            <td></td>
        </tr>
    </table>
@else
    <table class="panels avoid-break" style="margin-top: 4mm; margin-bottom: 0;">
        <tr>
            <td>
                <span class="badge">Awaiting payment</span>
                <p class="note" style="margin-top: 1.5mm;">
                    Quote order {{ $order->number }} with your payment so it is matched
                    to this order.
                </p>
            </td>
            <td></td>
        </tr>
    </table>
@endif

@include('pdf.partials.totals', ['totals' => $totals])

@if ($payment)
    <div class="block avoid-break">
        <h3>Payment</h3>
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
            @if ($payment->refunded_minor > 0)
                <tr>
                    <td class="k">Refunded</td>
                    <td class="v">{{ \App\Support\Money::format($payment->refunded_minor, $payment->currency) }}</td>
                </tr>
            @endif
        </table>
    </div>
@endif

<div class="block avoid-break">
    <h3>Notes</h3>
    <p class="small" style="margin: 0 0 2mm 0;">
        This invoice covers every brand in order {{ $order->number }}.
        @if ($showVendorColumn)
            Each item names the brand that made and dispatched it, so a
            multi-brand order may arrive as more than one parcel at no extra cost.
        @endif
    </p>
    <p class="small" style="margin: 0;">
        {{ $brandName }} is a product of {{ $operator['name'] }}@if ($operator['registration_number']), RC {{ $operator['registration_number'] }}@endif.
        @if ($operator['tin'])
            TIN {{ $operator['tin'] }}.
        @endif
        Goods remain the property of the brand that made them until paid for in full.
        Returns are governed by our Returns Policy at {{ rtrim((string) config('app.url'), '/') }}/pages/returns-policy.
    </p>
</div>
