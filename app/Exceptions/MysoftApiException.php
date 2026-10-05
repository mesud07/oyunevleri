<?php

declare(strict_types=1);

namespace App\Exceptions;

class MysoftApiException extends \RuntimeException
{
    public function __construct(string $message, public readonly ?string $mysoftCode = null, public readonly int $httpStatus = 0, public readonly ?array $response = null)
    {
        parent::__construct($message, $httpStatus);
    }
}
