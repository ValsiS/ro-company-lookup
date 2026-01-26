<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class AddressSetNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'anaf' => 'anaf',
        'registered_office' => 'sediu_social',
    ];

    protected array $en = [
        'anaf' => 'tax_authority',
        'registered_office' => 'registered_office',
    ];
}
