<?php

namespace App\Enums;

enum AdAudience: string
{
    case Everyone = 'everyone';
    case Guests = 'guests';
    case SignedIn = 'signed_in';
    case NewCustomers = 'new_customers';
    case ReturningCustomers = 'returning_customers';

    public function label(): string
    {
        return match ($this) {
            self::Everyone => 'Everyone',
            self::Guests => 'Guests only',
            self::SignedIn => 'Signed-in shoppers',
            self::NewCustomers => 'New customers (no paid order yet)',
            self::ReturningCustomers => 'Returning customers (has paid before)',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
