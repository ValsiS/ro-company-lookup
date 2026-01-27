<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Support;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class DateHelper
{
    public const CACHE_DATETIME_FORMAT = 'Y-m-d H:i:s';

    public static function normalizeDate(?DateTimeInterface $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) {
            return $date;
        }

        if ($date instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($date);
        }

        return self::now();
    }

    public static function now(): DateTimeImmutable
    {
        return CarbonImmutable::now(self::timezone());
    }

    public static function formatDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }

    public static function formatOutputDate(?DateTimeInterface $date): ?string
    {
        if ($date === null) {
            return null;
        }

        $format = self::resolveOutputFormat();

        return $date->format($format);
    }

    public static function resolveOutputFormat(?string $language = null, ?string $override = null): string
    {
        if (is_string($override) && $override !== '') {
            return $override;
        }

        $language = $language ?: (string) config('ro-company-lookup.language', 'ro');
        $formats = config('ro-company-lookup.date_output_formats', []);
        if (is_array($formats) && isset($formats[$language]) && is_string($formats[$language])) {
            return $formats[$language];
        }

        return (string) config('ro-company-lookup.date_output_format', 'Y-m-d');
    }

    /**
     * @template T
     *
     * @param  callable():T  $callback
     * @return T
     */
    public static function withOutputFormat(?string $language, ?string $override, callable $callback): mixed
    {
        $previous = config('data.date_format');
        $format = self::resolveOutputFormat($language, $override);
        config(['data.date_format' => $format]);

        try {
            return $callback();
        } finally {
            config(['data.date_format' => $previous]);
        }
    }

    public static function parseDate(?string $value): ?DateTimeImmutable
    {
        if (! $value) {
            return null;
        }

        $timezone = new DateTimeZone(self::timezone());

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value, $timezone);
        if ($date !== false) {
            return $date;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value, $timezone);
        if ($date !== false) {
            return $date;
        }

        try {
            return new DateTimeImmutable($value, $timezone);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public static function parseCacheDateTime(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat(self::CACHE_DATETIME_FORMAT, $value, new DateTimeZone(self::timezone()));

        return $date ?: null;
    }

    protected static function timezone(): string
    {
        return (string) config('ro-company-lookup.timezone', 'Europe/Bucharest');
    }
}
