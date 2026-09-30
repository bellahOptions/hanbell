@php
    /*
     * Credit note.
     *
     * Issued when money goes back to the customer — a refund or a cancellation
     * after payment. It is the accounting counterpart to the original invoice
     * and must reference it, so the pair reconciles.
     */
    $slot = view('pdf.partials.credit-note-body', get_defined_vars())->render();
@endphp

@include('pdf.layouts.base', ['slot' => $slot])
