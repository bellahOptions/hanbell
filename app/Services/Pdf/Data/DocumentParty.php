<?php

namespace App\Services\Pdf\Data;

/**
 * A party on a document — who it is from, or who it is to.
 *
 * Kept as a value object rather than an array so a template cannot silently
 * print an undefined key, and so the "omit blank lines" rule lives in one place.
 */
final readonly class DocumentParty
{
    /**
     * @param  array<int,string>  $addressLines
     */
    public function __construct(
        public string $name,
        public array $addressLines = [],
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $registrationNumber = null,
        public ?string $tin = null,
        public ?string $vatNumber = null,
        public ?string $label = null,
    ) {}

    /** @return array<int,string> */
    public function contactLines(): array
    {
        return array_values(array_filter([
            $this->email,
            $this->phone,
        ]));
    }

    /**
     * Identity lines that only appear when genuinely set.
     *
     * A Nigerian invoice is expected to carry the issuer's RC number and TIN,
     * but printing "RC: —" on a document that has none is worse than omitting
     * the line, so blank values are dropped here rather than in every template.
     *
     * @return array<int,string>
     */
    public function identityLines(): array
    {
        return array_values(array_filter([
            $this->registrationNumber ? 'RC: '.$this->registrationNumber : null,
            $this->tin ? 'TIN: '.$this->tin : null,
            $this->vatNumber ? 'VAT: '.$this->vatNumber : null,
        ]));
    }

    public function hasIdentity(): bool
    {
        return $this->identityLines() !== [];
    }

    /** Whether anything at all would be printed for this party. */
    public function isBlank(): bool
    {
        return trim($this->name) === ''
            && $this->addressLines === []
            && $this->contactLines() === [];
    }
}
