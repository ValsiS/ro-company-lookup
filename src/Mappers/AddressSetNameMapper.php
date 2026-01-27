<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class AddressSetNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'fiscal_domicile' => 'domiciliu_fiscal',
        'registered_office' => 'sediu_social',
    ];

    protected array $en = [
        'fiscal_domicile' => 'fiscal_domicile',
        'registered_office' => 'registered_office',
    ];
}
