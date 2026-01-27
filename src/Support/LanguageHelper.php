<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Support;

final class LanguageHelper
{
    /**
     * @template T
     *
     * @param  callable():T  $callback
     * @return T
     */
    public static function withLanguage(?string $language, callable $callback): mixed
    {
        if ($language === null || $language === '') {
            return $callback();
        }

        $previous = config('ro-company-lookup.language');
        config(['ro-company-lookup.language' => $language]);

        try {
            return $callback();
        } finally {
            config(['ro-company-lookup.language' => $previous]);
        }
    }
}
