<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class FirmaNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'cui' => 'cui',
        'registration_number' => 'j',
        'name_mfinante' => 'nume_mfinante',
        'name_recom' => 'nume_recom',
        'profile' => 'profil',
    ];

    protected array $en = [
        'cui' => 'cui',
        'registration_number' => 'trade_register_number',
        'name_mfinante' => 'ministry_of_finance_name',
        'name_recom' => 'recom_name',
        'profile' => 'profile',
    ];
}
