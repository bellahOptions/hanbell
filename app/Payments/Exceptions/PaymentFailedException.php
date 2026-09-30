<?php

namespace App\Payments\Exceptions;

use RuntimeException;

class PaymentFailedException extends RuntimeException
{
    public function __construct(string $message, public readonly array $context = [])
    {
        parent::__construct($message);
    }
}
