<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use Spatie\LaravelData\Data;

class LookupResultData extends Data
{
    public const STATUS_OK = 'ok';

    public const STATUS_NOT_FOUND = 'not_found';

    public const STATUS_INVALID = 'invalid';

    public const STATUS_ERROR = 'error';

    public function __construct(
        public string $status,
        public ?CompanySimpleData $data = null,
        public ?string $error = null,
        public ?string $message = null,
        public ?int $error_code = null
    ) {}

    public static function ok(CompanySimpleData $data): self
    {
        return new self(self::STATUS_OK, $data);
    }

    public static function notFound(CompanySimpleData $data): self
    {
        return new self(self::STATUS_NOT_FOUND, $data, 'not_found', 'Company not found.');
    }

    public static function invalid(string $message, string $error = 'invalid_cui'): self
    {
        return new self(self::STATUS_INVALID, null, $error, $message);
    }

    public static function error(string $message, string $error = 'lookup_failed', ?int $code = null): self
    {
        return new self(self::STATUS_ERROR, null, $error, $message, $code);
    }

    public function isOk(): bool
    {
        return $this->status === self::STATUS_OK;
    }

    public function isNotFound(): bool
    {
        return $this->status === self::STATUS_NOT_FOUND;
    }

    public function exists(): bool
    {
        return $this->status === self::STATUS_OK;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $exists = $this->status === self::STATUS_OK;
        $valid = $this->status !== self::STATUS_INVALID;

        $summary = [
            'exists' => $exists,
            'valid' => $valid,
            'status' => $this->status,
            'message' => $this->message,
            'error' => $this->error,
            'code' => $this->error_code,
            'cui' => null,
            'name' => null,
            'caen' => null,
            'registration_date' => null,
            'vat_payer' => null,
        ];

        if ($this->data === null) {
            return $summary;
        }

        $data = $this->data->summary();
        $summary['cui'] = $data['cui'] ?? null;
        $summary['name'] = $data['name'] ?? null;
        $summary['caen'] = $data['caen'] ?? null;
        $summary['registration_date'] = $data['registration_date'] ?? null;
        $summary['vat_payer'] = $data['vat_payer'] ?? null;

        return $summary;
    }
}
