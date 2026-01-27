<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class FirmaNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'cui' => 'cui',
        'registration_number' => 'nr_reg_com',
        'name' => 'denumire',
        'profile' => 'profil',
    ];

    protected array $en = [
        'cui' => 'cui',
        'registration_number' => 'registration_number',
        'name' => 'name',
        'profile' => 'profile',
    ];
}
