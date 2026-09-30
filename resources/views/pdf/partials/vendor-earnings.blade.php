{{-- Net earnings panel shown beside a vendor statement's totals. --}}
<table class="facts" style="margin-top: 1mm;">
    <tr>
        <td class="k">Goods value</td>
        <td class="v">{{ $totals->formattedSubtotal() }}</td>
    </tr>
    <tr>
        <td class="k">Commission ({{ rtrim(rtrim(number_format((float) $vendorOrder->commission_percent, 2), '0'), '.') }}%)</td>
        <td class="v">&minus;{{ $totals->formattedCommission() }}</td>
    </tr>
    <tr>
        <td class="k" style="font-weight: bold; color: #0b0b0a;">Net payable to you</td>
        <td class="v" style="color: #178508;">{{ $totals->formattedPayout() }}</td>
    </tr>
</table>

@if ($vendorOrder->status->value === 'pending')
    <p class="note">This order is awaiting payment and is not yet payable.</p>
@elseif ($vendorOrder->status->value === 'refunded')
    <p class="note">This order was refunded; the figures above have been reversed.</p>
@endif
