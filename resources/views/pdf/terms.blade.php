@php
    /*
     * Terms of Service, as a formal document.
     *
     * The clauses themselves come from the administrator-editable CMS page, so
     * there is one source of truth: changing the terms on the site changes them
     * here too. What is added around them are the elements a formal, signed-
     * capable document needs and a web page does not — a preamble naming the
     * operating entity, a contents list, the governing jurisdiction, a
     * complaints procedure, and a document control block stating which version
     * was issued and when.
     */
    $slot = view('pdf.partials.terms-body', get_defined_vars())->render();
@endphp

@include('pdf.layouts.base', ['slot' => $slot])
