<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Drivers;

use Valsis\RoCompanyLookup\Data\CompanySimpleData;

final readonly class DriverResponse
{
    /**
     * @param  array<string, mixed>|null  $raw
     */
    public function __construct(
        public CompanySimpleData $data,
        public ?array $raw = null
    ) {}
}
