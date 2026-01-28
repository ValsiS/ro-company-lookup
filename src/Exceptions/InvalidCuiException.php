<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Exceptions;

use InvalidArgumentException;

class InvalidCuiException extends InvalidArgumentException
{
    public const ERROR_INVALID = 'invalid_cui';

    public const ERROR_TOO_SHORT = 'invalid_cui_too_short';

    public const ERROR_TOO_LONG = 'invalid_cui_too_long';

    private string $error;

    private ?string $digits;

    public function __construct(string $message, string $error = self::ERROR_INVALID, ?string $digits = null)
    {
        parent::__construct($message);

        $this->error = $error;
        $this->digits = $digits;
    }

    public function error(): string
    {
        return $this->error;
    }

    public function digits(): ?string
    {
        return $this->digits;
    }
}
