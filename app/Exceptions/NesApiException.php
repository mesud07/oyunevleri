<?php

declare(strict_types=1);

namespace App\Exceptions;

class NesApiException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $nesCode = null,
        public readonly int $httpStatus = 0,
        public readonly ?array $response = null,
        public readonly bool $sonucBelirsiz = false,
    ) {
        parent::__construct($message);
    }
}
