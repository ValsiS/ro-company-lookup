<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Support;

use Valsis\RoCompanyLookup\Exceptions\InvalidCuiException;

class NormalizeCui
{
    private const MIN_DIGITS = 2;

    private const MAX_DIGITS = 10;

    public static function normalize(int|string $cui): int
    {
        $digits = '';

        if (is_int($cui)) {
            $value = $cui;
            $digits = ltrim((string) $cui, '-');
        } else {
            $normalized = strtoupper(trim($cui));
            $normalized = preg_replace('/^RO\\s*/', '', $normalized) ?? '';
            $digits = preg_replace('/\\D/', '', $normalized) ?? '';
            $value = (int) $digits;
        }

        if ($digits === '') {
            throw new InvalidCuiException(
                'CUI is not valid.',
                InvalidCuiException::ERROR_INVALID,
                $digits
            );
        }

        $length = strlen($digits);
        if ($length < self::MIN_DIGITS) {
            throw new InvalidCuiException(sprintf(
                'CUI is too short. Minimum length is %d digits.',
                self::MIN_DIGITS
            ), InvalidCuiException::ERROR_TOO_SHORT, $digits);
        }

        if ($length > self::MAX_DIGITS) {
            throw new InvalidCuiException(sprintf(
                'CUI is too long. Maximum length is %d digits.',
                self::MAX_DIGITS
            ), InvalidCuiException::ERROR_TOO_LONG, $digits);
        }

        if ($value <= 0) {
            throw new InvalidCuiException(
                'CUI is not valid.',
                InvalidCuiException::ERROR_INVALID,
                $digits
            );
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
