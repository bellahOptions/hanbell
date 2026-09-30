@php
    /*
     * Payment receipt.
     *
     * Deliberately narrower than an invoice: a receipt is proof that money
     * changed hands, so it leads with the payment — method, reference, date,
     * amount — and shows the goods only as context. It is issued on A5 because
     * a receipt is usually printed or kept on a phone, not filed as an A4 page.
     */
    $slot = view('pdf.partials.receipt-body', get_defined_vars())->render();
@endphp

{{-- A5 and compact: a receipt must fit on one sheet. --}}
@include('pdf.layouts.base', ['slot' => $slot, 'compact' => true])
