<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class CaenSetNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'principal_mfinante' => 'principal_mfinante',
        'principal_recom' => 'principal_recom',
    ];

    protected array $en = [
        'principal_mfinante' => 'primary_ministry_of_finance',
        'principal_recom' => 'primary_recom',
    ];
}
