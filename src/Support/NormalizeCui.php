<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Support;

use Valsis\RoCompanyLookup\Exceptions\InvalidCuiException;

class NormalizeCui
{
    public static function normalize(int|string $cui): int
    {
        if (is_int($cui)) {
            $value = $cui;
        } else {
            $normalized = strtoupper(trim($cui));
            $normalized = str_replace(['RO', ' '], '', $normalized);
            $normalized = preg_replace('/[^0-9]/', '', $normalized) ?? '';
            $value = (int) $normalized;
        }

        if ($value <= 0) {
            throw new InvalidCuiException('CUI must be a positive integer.');
        }

        return $value;
    }

    /**
     * @param  array<int|string>  $cuis
     * @return array<int>
     */
    public static function normalizeMany(array $cuis): array
    {
        $normalized = [];

        foreach ($cuis as $cui) {
            $normalized[] = self::normalize($cui);
        }

        return $normalized;
    }
}
