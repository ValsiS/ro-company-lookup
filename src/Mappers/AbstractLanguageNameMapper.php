<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

use Spatie\LaravelData\Mappers\NameMapper;

abstract class AbstractLanguageNameMapper implements NameMapper
{
    /**
     * @var array<string, string>
     */
    protected array $ro = [];

    /**
     * @var array<string, string>
     */
    protected array $en = [];

    public function map(string|int $name): string|int
    {
        if (is_int($name)) {
            return $name;
        }

        $language = strtolower((string) config('ro-company-lookup.language', 'ro'));
        $useRomanian = in_array($language, ['ro', 'rom', 'romanian', 'ro-ro'], true);
        $map = $useRomanian ? $this->ro : $this->en;

        return $map[$name] ?? $name;
    }
}
