<?php

namespace App\Services\Integrations;

use RuntimeException;

class InvalidHokesenAssertionException extends RuntimeException
{
    public function __construct(
        string $message = 'Invalid assertion',
        private readonly string $reason = 'invalid_assertion'
    ) {
        parent::__construct($message);
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
