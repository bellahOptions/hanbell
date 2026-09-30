{{--
    Two-column party panel: who the document is from, and who it is for.

    Left column is the issuer (the legal entity), right column is the customer,
    separated from a facts table so a template can put order metadata beside the
    recipient.
--}}
<table class="panels">
    <tr>
        <td>
            <p class="panel-label">{{ $party->label ?? 'Party' }}</p>
            <p class="party-name">{{ $party->name }}</p>

            @foreach ($party->addressLines as $line)
                <p class="party-line">{{ $line }}</p>
            @endforeach

            @foreach ($party->contactLines() as $line)
                <p class="party-meta">{{ $line }}</p>
            @endforeach

            @if ($party->hasIdentity())
                <p class="party-meta" style="margin-top: 2pt;">
                    {{ implode(' &nbsp;·&nbsp; ', $party->identityLines()) }}
                </p>
            @endif

            @if (! empty($partyExtra))
                {!! $partyExtra !!}
            @endif
        </td>
        <td>
            {!! $rightColumn ?? '' !!}
        </td>
    </tr>
</table>
