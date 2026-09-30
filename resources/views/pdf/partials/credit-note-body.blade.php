@php
    $refundedMinor = (int) ($creditMinor ?? $order->payments->sum('refunded_minor'));
    $isFullCredit = $refundedMinor >= (int) $order->total_minor;
@endphp

<table class="panels">
    <tr>
        <td>
            <p class="panel-label">Credited to</p>
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
                    <td class="k">Credit note number</td>
                    <td class="v">{{ $creditNoteNumber }}</td>
                </tr>
                <tr>
                    <td class="k">Against invoice</td>
                    <td class="v">{{ $invoiceNumber }}</td>
                </tr>
                <tr>
                    <td class="k">Order reference</td>
                    <td class="v">{{ $order->number }}</td>
                </tr>
                <tr>
                    <td class="k">Date issued</td>
                    <td class="v">{{ $issueDate->format('j M Y') }}</td>
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
    <h3>{{ $isFullCredit ? 'Full credit' : 'Partial credit' }}</h3>

    <table class="totals" style="width: 100%;">
        <tr>
            <td class="k">Original invoice total</td>
            <td class="v">{{ \App\Support\Money::format((int) $order->total_minor, $order->currency) }}</td>
        </tr>
        <tr class="grand">
            <td class="k">Amount credited</td>
            <td class="v">{{ \App\Support\Money::format($refundedMinor, $order->currency) }}</td>
        </tr>
        <tr class="balance">
            <td class="k">
                {{ $isFullCredit ? 'Balance remaining' : 'Still payable by the customer' }}
            </td>
            <td class="v">
                {{ \App\Support\Money::format(max(0, (int) $order->total_minor - $refundedMinor), $order->currency) }}
            </td>
        </tr>
    </table>
</div>

<div class="block avoid-break">
    <h3>Reason</h3>
    <p class="small" style="margin: 0;">{{ $reason ?: 'Order refunded in accordance with the HanbellShop Returns Policy.' }}</p>
</div>

@if ($order->items->isNotEmpty())
    <div class="block avoid-break">
        <h3>Items credited</h3>
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 62%;">Item</th>
                    <th class="num" style="width: 12%;">Qty</th>
                    <th class="num" style="width: 26%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>
                            <div class="item-name">{{ $item->product_name }}</div>
                            <div class="item-meta">{{ $item->vendor_name }}</div>
                        </td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">{{ $item->formattedLineTotal() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<p class="small" style="margin-top: 4mm;">
    This credit note cancels {{ $isFullCredit ? 'the whole of' : 'part of' }} invoice {{ $invoiceNumber }}.
    {{ $brandName }} is a product of {{ $operator['name'] }}@if ($operator['registration_number']), RC {{ $operator['registration_number'] }}@endif.
    Refunds are returned to the original payment method; how quickly they appear
    depends on your bank or payment provider.
</p>
