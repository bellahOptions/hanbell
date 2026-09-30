<?php

namespace App\Payments\Exceptions;

use RuntimeException;

class PaymentGatewayNotConfiguredException extends RuntimeException
{
    public function __construct(public readonly string $provider)
    {
        parent::__construct(
            "The {$provider} payment gateway has no credentials configured, so it cannot be used. ".
            'Add the API keys to your .env file to enable it.'
        );
    }
}
