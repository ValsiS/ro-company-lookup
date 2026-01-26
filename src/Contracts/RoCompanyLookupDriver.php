<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Contracts;

use DateTimeInterface;
use Valsis\RoCompanyLookup\Drivers\DriverResponse;

interface RoCompanyLookupDriver
{
    public function lookup(int $cui, DateTimeInterface $date): DriverResponse;

    /**
     * @param  array<int>  $cuis
     * @return array<int, DriverResponse>
     */
    public function batch(array $cuis, DateTimeInterface $date): array;
}
