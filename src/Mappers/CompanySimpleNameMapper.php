<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class CompanySimpleNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'address' => 'adresa',
        'caen' => 'cod_caen',
        'contact' => 'date_contact',
        'company' => 'firma',
        'legal' => 'forma_juridica',
        'vat' => 'statut_tva',
        'meta' => 'meta',
    ];

    protected array $en = [
        'address' => 'address',
        'caen' => 'caen_code',
        'contact' => 'contact_details',
        'company' => 'company',
        'legal' => 'legal_form',
        'vat' => 'vat_status',
        'meta' => 'meta',
    ];
}
