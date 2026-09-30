{{--
    The totals block.

    A fixed-width table inside a full-width, right-aligned wrapper.

    Two earlier approaches both failed and are worth not repeating:

      1. A two-cell wrapper (content cell + fixed-width totals cell): dompdf's
         auto table layout gave the totals cell less than its stated width once
         the sibling held content, so the numbers overlapped the panel beside
         them.

      2. `align="right"` on the table alone: the table still resolved to 100% of
         its container, so it spanned the full width and overlapped whatever
         followed it.

    Any panel a template wants near the totals belongs in the flow ABOVE this
    block, not beside it.
--}}
<div style="width: 100%; text-align: right;">
    <table class="totals" style="width: 74mm; margin-left: auto;">
    <tr>
        <td class="k">Subtotal</td>
        <td class="v">{{ $totals->formattedSubtotal() }}</td>
    </tr>

    @if ($totals->formattedDiscount())
        <tr>
            <td class="k">Discount</td>
            <td class="v discount-v">&minus;{{ $totals->formattedDiscount() }}</td>
        </tr>
    @endif

    @if ($totals->shippingMinor > 0 || $totals->subtotalMinor > 0)
        <tr>
            <td class="k">Delivery</td>
            <td class="v">
                @if ($totals->shippingMinor === 0 && $totals->subtotalMinor > 0)
                    Free
                @else
                    {{ $totals->formattedShipping() }}
                @endif
            </td>
        </tr>
    @endif

    @if ($totals->formattedTax())
        <tr>
            <td class="k">{{ $totals->taxLabel() }}</td>
            <td class="v">{{ $totals->formattedTax() }}</td>
        </tr>
    @endif

    @if ($totals->formattedCommission())
        <tr>
            <td class="k">Commission withheld</td>
            <td class="v discount-v">&minus;{{ $totals->formattedCommission() }}</td>
        </tr>
    @endif

    <tr class="grand">
        <td class="k">
            {{ $totals->formattedPayout() ? 'Net payable' : 'Total' }}
        </td>
        <td class="v">
            {{ $totals->formattedPayout() ?? $totals->formattedTotal() }}
        </td>
    </tr>

    @if ($totals->formattedPaid() && $totals->paidMinor > 0)
        <tr>
            <td class="k">Paid</td>
            <td class="v">&minus;{{ $totals->formattedPaid() }}</td>
        </tr>
    @endif

    @if ($totals->balanceMinor !== null)
        <tr class="balance">
            <td class="k">{{ $totals->hasBalance() ? 'Balance due' : 'Balance' }}</td>
            <td class="v">{{ $totals->formattedBalance() }}</td>
        </tr>
    @endif
</table>
</div>

