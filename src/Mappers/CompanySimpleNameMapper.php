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
        'vat_collection' => 'tva_incasare',
        'inactive_status' => 'stare_inactiv',
        'split_vat' => 'split_tva',
        'legal' => 'forma_juridica',
        'vat' => 'statut_tva',
        'meta' => 'meta',
    ];

    protected array $en = [
        'address' => 'address',
        'caen' => 'caen_code',
        'contact' => 'contact_details',
        'company' => 'company',
        'vat_collection' => 'vat_collection',
        'inactive_status' => 'inactive_status',
        'split_vat' => 'split_vat',
        'legal' => 'legal_form',
        'vat' => 'vat_status',
        'meta' => 'meta',
    ];
}
